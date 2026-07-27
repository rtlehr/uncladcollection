<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\RecentlyViewedAsset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RecentlyViewedAssetService
{
    private const SESSION_KEY = 'discovery.recently_viewed_assets';

    public function record(Request $request, Asset $asset, ?User $user, ?string $source = null): bool
    {
        return $user
            ? $this->recordForUser($request, $asset, $user, $source)
            : $this->recordForGuest($request, $asset, $source);
    }

    /** @return Collection<int, Asset> */
    public function recent(Request $request, ?User $user, ?int $excludeAssetId = null): Collection
    {
        if ($user) {
            $this->mergeGuestHistory($request, $user);
        }

        $ids = $user
            ? RecentlyViewedAsset::query()
                ->where('user_id', $user->id)
                ->when($excludeAssetId, fn ($query, int $id) => $query->where('asset_id', '!=', $id))
                ->latest('last_viewed_at')
                ->limit((int) config('discovery.recently_viewed.limit', 8) * 2)
                ->pluck('asset_id')
            : collect($request->session()->get(self::SESSION_KEY, []))
                ->reject(fn (array $entry) => (int) ($entry['asset_id'] ?? 0) === $excludeAssetId)
                ->pluck('asset_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        $assets = Asset::query()
            ->discoverable()
            ->whereKey($ids->all())
            ->with($this->cardRelations($user?->id))
            ->get()
            ->keyBy('id');

        return $ids
            ->map(fn ($id) => $assets->get((int) $id))
            ->filter()
            ->take((int) config('discovery.recently_viewed.limit', 8))
            ->values();
    }

    private function recordForUser(Request $request, Asset $asset, User $user, ?string $source): bool
    {
        $this->mergeGuestHistory($request, $user);

        $existing = RecentlyViewedAsset::query()
            ->where('user_id', $user->id)
            ->where('asset_id', $asset->id)
            ->first();

        $countable = ! $existing?->last_viewed_at
            || $existing->last_viewed_at->lte(now()->subMinutes((int) config('discovery.recently_viewed.deduplication_minutes', 30)));

        RecentlyViewedAsset::query()->updateOrCreate(
            ['user_id' => $user->id, 'asset_id' => $asset->id],
            [
                'source' => $source,
                'last_viewed_at' => now(),
                'view_count' => ($existing?->view_count ?? 0) + ($countable ? 1 : 0),
            ],
        );

        $this->trimUserHistory($user);

        return $countable;
    }

    private function recordForGuest(Request $request, Asset $asset, ?string $source): bool
    {
        $history = collect($request->session()->get(self::SESSION_KEY, []));
        $existing = $history->firstWhere('asset_id', $asset->id);
        $cutoff = now()->subMinutes((int) config('discovery.recently_viewed.deduplication_minutes', 30));
        $lastViewed = isset($existing['last_viewed_at']) ? CarbonImmutable::parse($existing['last_viewed_at']) : null;
        $countable = ! $lastViewed || $lastViewed->lte($cutoff);

        $history = $history
            ->reject(fn (array $entry) => (int) ($entry['asset_id'] ?? 0) === $asset->id)
            ->prepend([
                'asset_id' => $asset->id,
                'source' => $source,
                'last_viewed_at' => now()->toISOString(),
            ])
            ->take((int) config('discovery.recently_viewed.storage_limit', 24))
            ->values();

        $request->session()->put(self::SESSION_KEY, $history->all());

        return $countable;
    }

    private function mergeGuestHistory(Request $request, User $user): void
    {
        $history = collect($request->session()->pull(self::SESSION_KEY, []));

        foreach ($history->reverse() as $entry) {
            $assetId = (int) ($entry['asset_id'] ?? 0);
            if ($assetId < 1 || ! Asset::query()->whereKey($assetId)->discoverable()->exists()) {
                continue;
            }

            RecentlyViewedAsset::query()->updateOrCreate(
                ['user_id' => $user->id, 'asset_id' => $assetId],
                [
                    'source' => $entry['source'] ?? null,
                    'last_viewed_at' => $entry['last_viewed_at'] ?? now(),
                ],
            );
        }
    }

    private function trimUserHistory(User $user): void
    {
        $keepIds = RecentlyViewedAsset::query()
            ->where('user_id', $user->id)
            ->latest('last_viewed_at')
            ->limit((int) config('discovery.recently_viewed.storage_limit', 100))
            ->pluck('id');

        RecentlyViewedAsset::query()
            ->where('user_id', $user->id)
            ->when($keepIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $keepIds))
            ->delete();
    }

    private function cardRelations(?int $userId): array
    {
        $relations = [
            'collection:id,name,slug',
            'primaryPreviewFile',
            'posterFile',
            'activeFiles',
            'categories:id,name',
            'tags:id,name',
        ];

        if ($userId) {
            $relations['favorites'] = fn ($query) => $query->where('user_id', $userId);
        }

        return $relations;
    }
}
