<?php

namespace App\Services;

use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Models\Asset;
use App\Models\AssetFavorite;
use App\Models\User;
use App\Models\WishList;
use App\Models\WishListItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WishListService
{
    public function __construct(
        private readonly AnalyticsTracker $tracker,
    ) {
    }

    public function defaultList(User $user): WishList
    {
        return DB::transaction(function () use ($user): WishList {
            $existing = WishList::query()
                ->forUser($user->id)
                ->where('is_default', true)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            return WishList::query()->create([
                'user_id' => $user->id,
                'uuid' => (string) Str::uuid(),
                'name' => 'Favorites',
                'slug' => $this->uniqueSlug($user, 'favorites'),
                'visibility' => 'private',
                'is_default' => true,
                'sort_order' => 0,
                'last_activity_at' => now(),
            ]);
        });
    }

    public function createList(User $user, array $attributes): WishList
    {
        $list = WishList::query()->create([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'name' => trim($attributes['name']),
            'slug' => $this->uniqueSlug($user, $attributes['name']),
            'description' => $attributes['description'] ?? null,
            'visibility' => $attributes['visibility'] ?? 'private',
            'share_token' => ($attributes['visibility'] ?? 'private') === 'unlisted' ? Str::random(48) : null,
            'sort_order' => ((int) WishList::query()->forUser($user->id)->max('sort_order')) + 1,
            'last_activity_at' => now(),
        ]);

        $this->tracker->record(
            AnalyticsEventName::WishListCreated,
            subject: $list,
            user: $user,
            dimensions: ['visibility' => $list->visibility],
            source: 'customer_account',
            channel: 'onsite',
        );

        return $list;
    }

    public function add(User $user, WishList $list, Asset $asset): WishListItem
    {
        $this->guardOwner($user, $list);

        return DB::transaction(function () use ($user, $list, $asset): WishListItem {
            $item = WishListItem::query()->firstOrCreate(
                ['wish_list_id' => $list->id, 'asset_id' => $asset->id],
                [
                    'sort_order' => 0,
                    'price_snapshot_cents' => $asset->offerings()->where('is_active', true)->min('price_cents'),
                    'availability_snapshot' => $asset->is_active ? 'available' : 'unavailable',
                ],
            );

            if ($list->is_default) {
                AssetFavorite::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'asset_id' => $asset->id,
                ]);
            }

            $list->forceFill(['last_activity_at' => now()])->saveQuietly();
            $this->synchronizeCounts($asset);

            $this->tracker->record(
                AnalyticsEventName::WishListItemAdded,
                subject: $asset,
                user: $user,
                dimensions: ['wish_list_id' => $list->id, 'is_default' => $list->is_default],
                source: 'wish_list',
                channel: 'onsite',
            );

            return $item;
        });
    }

    public function remove(User $user, WishList $list, Asset $asset): void
    {
        $this->guardOwner($user, $list);

        DB::transaction(function () use ($user, $list, $asset): void {
            WishListItem::query()
                ->where('wish_list_id', $list->id)
                ->where('asset_id', $asset->id)
                ->delete();

            if ($list->is_default) {
                AssetFavorite::query()
                    ->where('user_id', $user->id)
                    ->where('asset_id', $asset->id)
                    ->delete();
            }

            $list->forceFill(['last_activity_at' => now()])->saveQuietly();
            $this->synchronizeCounts($asset);

            $this->tracker->record(
                AnalyticsEventName::WishListItemRemoved,
                subject: $asset,
                user: $user,
                dimensions: ['wish_list_id' => $list->id, 'is_default' => $list->is_default],
                source: 'wish_list',
                channel: 'onsite',
            );
        });
    }

    public function move(User $user, WishListItem $item, WishList $destination, bool $copy = false): void
    {
        $item->loadMissing('wishList', 'asset');
        $this->guardOwner($user, $item->wishList);
        $this->guardOwner($user, $destination);

        DB::transaction(function () use ($user, $item, $destination, $copy): void {
            $this->add($user, $destination, $item->asset);

            if (! $copy && $item->wish_list_id !== $destination->id) {
                $this->remove($user, $item->wishList, $item->asset);
            }
        });
    }

    public function updateSharing(User $user, WishList $list, string $visibility): WishList
    {
        $this->guardOwner($user, $list);

        $list->update([
            'visibility' => $visibility,
            'share_token' => $visibility === 'unlisted'
                ? ($list->share_token ?: Str::random(48))
                : null,
        ]);

        return $list->refresh();
    }

    public function delete(User $user, WishList $list): void
    {
        $this->guardOwner($user, $list);
        abort_if($list->is_default, 422, 'The default Favorites list cannot be deleted.');

        $assets = $list->items()->with('asset')->get()->pluck('asset')->filter();

        DB::transaction(function () use ($user, $list, $assets): void {
            $list->delete();

            foreach ($assets as $asset) {
                $this->synchronizeCounts($asset);
            }
        });
    }

    public function guardOwner(User $user, WishList $list): void
    {
        abort_unless($list->user_id === $user->id, 404);
    }

    private function synchronizeCounts(Asset $asset): void
    {
        $count = WishListItem::query()
            ->where('wish_list_items.asset_id', $asset->id)
            ->join('wish_lists', 'wish_lists.id', '=', 'wish_list_items.wish_list_id')
            ->distinct('wish_lists.user_id')
            ->count('wish_lists.user_id');

        $asset->updateQuietly(['favorites_count' => $count]);
        $asset->legacyImage?->updateQuietly(['favorites_count' => $count]);
    }

    private function uniqueSlug(User $user, string $name): string
    {
        $base = Str::slug($name) ?: 'list';
        $slug = $base;
        $suffix = 2;

        while (WishList::query()->forUser($user->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
