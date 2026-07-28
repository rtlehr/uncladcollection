<?php

namespace App\Services\Account;

use App\Models\Asset;
use App\Models\License;
use App\Models\User;
use App\Services\PersonalizedRecommendationService;
use App\Services\PublicAssetCatalogService;
use Illuminate\Support\Collection;

class AccountDashboardService
{
    public function __construct(
        private readonly PersonalizedRecommendationService $recommendations,
        private readonly PublicAssetCatalogService $catalog,
    ) {}

    public function forUser(User $user): array
    {
        $licenses = License::query()
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereHas('asset')->orWhereHas('image');
            });

        $recentLicenses = (clone $licenses)
            ->with(['asset.primaryPreviewFile', 'image', 'order'])
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (License $license): array => $this->formatLicense($license))
            ->values();

        $recentlyViewed = $user->recentlyViewedAssets()
            ->with(['asset.collection:id,name,slug', 'asset.categories:id,name', 'asset.tags:id,name', 'asset.activeFiles', 'asset.offerings' => fn ($query) => $query->where('is_active', true), 'asset.favorites' => fn ($query) => $query->where('user_id', $user->id)])
            ->latest('last_viewed_at')
            ->limit(4)
            ->get()
            ->pluck('asset')
            ->filter()
            ->map(fn ($asset): array => $this->catalog->formatCard($asset))
            ->values();

        $recommendations = $this->formatRecommendations(
            $user,
            $this->recommendations->forUser($user, 4),
        );

        return [
            'summary' => [
                'licenses' => (clone $licenses)->count(),
                'active_licenses' => (clone $licenses)->where('status', License::STATUS_ACTIVE)->count(),
                'favorites' => $user->assetFavorites()->count(),
                'downloads_used' => (int) (clone $licenses)->sum('downloads_used'),
            ],
            'alerts' => $this->alerts($licenses),
            'recent_licenses' => $recentLicenses,
            'recently_viewed' => $recentlyViewed,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Epic 5 recommendation results use a compact discovery-card contract.
     * The shared GalleryCard requires the complete marketplace-card contract,
     * so normalize each recommendation through PublicAssetCatalogService.
     */
    private function formatRecommendations(User $user, Collection $recommendations): Collection
    {
        $ids = $recommendations
            ->pluck('id')
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $assets = Asset::query()
            ->whereKey($ids)
            ->with([
                'collection:id,name,slug',
                'categories:id,name',
                'tags:id,name',
                'activeFiles',
                'primaryPreviewFile',
                'posterFile',
                'offerings' => fn ($query) => $query->where('is_active', true),
                'favorites' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->get()
            ->keyBy('id');

        return $recommendations
            ->map(function (array $recommendation) use ($assets): ?array {
                /** @var Asset|null $asset */
                $asset = $assets->get((int) ($recommendation['id'] ?? 0));

                if (! $asset) {
                    return null;
                }

                $card = $this->catalog->formatCard($asset);

                // Retain Epic 5 attribution links and explanation metadata.
                if (is_string($recommendation['href'] ?? null) && $recommendation['href'] !== '') {
                    $card['href'] = $recommendation['href'];
                }

                $card['reason'] = $recommendation['reason'] ?? null;
                $card['relevance_score'] = $recommendation['relevance_score'] ?? null;

                return $card;
            })
            ->filter()
            ->values();
    }

    private function alerts($licenses): Collection
    {
        $alerts = collect();

        $expiring = (clone $licenses)
            ->where('status', License::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->count();

        if ($expiring > 0) {
            $alerts->push([
                'type' => 'warning',
                'title' => 'Licenses expiring soon',
                'message' => $expiring === 1 ? 'One license expires within 30 days.' : "{$expiring} licenses expire within 30 days.",
                'href' => route('account.library.index'),
            ]);
        }

        $unavailable = (clone $licenses)
            ->whereIn('status', [License::STATUS_EXPIRED, License::STATUS_REVOKED, License::STATUS_REFUNDED])
            ->count();

        if ($unavailable > 0) {
            $alerts->push([
                'type' => 'info',
                'title' => 'License history available',
                'message' => 'Your library keeps purchase records even when a license is no longer active.',
                'href' => route('account.library.index'),
            ]);
        }

        return $alerts;
    }

    private function formatLicense(License $license): array
    {
        $asset = $license->asset;
        $image = $license->image;

        return [
            'id' => $license->id,
            'title' => $asset?->title ?? $image?->title ?? 'Licensed asset',
            'license_name' => $license->license_name,
            'status' => $license->isActive() ? 'active' : $license->status,
            'purchased_at' => $license->created_at?->format('M j, Y'),
            'preview_url' => $asset?->primaryPreviewFile
                ? route('assets.preview', [$asset, $asset->primaryPreviewFile])
                : $image?->thumbnail_url,
            'detail_url' => route('account.licenses.show', $license),
            'order_number' => $license->order?->order_number,
        ];
    }
}
