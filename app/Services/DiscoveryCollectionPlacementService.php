<?php

namespace App\Services;

use App\Enums\DiscoverySource;
use App\Models\Asset;
use App\Models\DiscoveryCollectionPlacement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DiscoveryCollectionPlacementService
{
    public function __construct(
        private readonly DiscoveryCacheService $cache,
    ) {}

    public function homepage(bool $authenticated): array
    {
        $audience = $authenticated ? 'authenticated' : 'guest';
        $key = $this->cache->key('homepage-collection-placements', ['audience' => $audience]);

        return Cache::remember($key, now()->addMinutes(10), function () use ($authenticated): array {
            return DiscoveryCollectionPlacement::query()
                ->currentlyActive()
                ->forAudience($authenticated)
                ->whereIn('placement', ['homepage_primary', 'homepage_secondary'])
                ->whereHas('collection', fn ($query) => $query->where('is_active', true))
                ->with(['collection'])
                ->orderBy('placement')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->groupBy('placement')
                ->map(fn (Collection $placements) => $placements
                    ->take(6)
                    ->map(fn (DiscoveryCollectionPlacement $placement) => $this->format($placement))
                    ->values()
                    ->all())
                ->all();
        });
    }

    private function format(DiscoveryCollectionPlacement $placement): array
    {
        $collection = $placement->collection;
        $fallbackAsset = Asset::query()
            ->discoverable()
            ->where('collection_id', $collection->id)
            ->with(['activeFiles'])
            ->orderByDesc('is_featured')
            ->orderByDesc('purchases_count')
            ->first();

        $coverUrl = $collection->cover_image_url;
        if (! $coverUrl && $fallbackAsset) {
            $preview = $fallbackAsset->activeFiles
                ->first(fn ($file) => in_array($file->role->value, ['preview', 'poster', 'thumbnail', 'icon', 'primary'], true));
            $coverUrl = $preview?->publicUrl();
        }

        $assetCount = Asset::query()
            ->discoverable()
            ->where('collection_id', $collection->id)
            ->count();

        $source = $placement->content_type === 'seasonal'
            ? DiscoverySource::SeasonalCollection->value
            : DiscoverySource::FeaturedCollection->value;

        return [
            'id' => $placement->id,
            'placement' => $placement->placement,
            'content_type' => $placement->content_type,
            'eyebrow' => $placement->eyebrow ?: ($placement->content_type === 'seasonal' ? 'Seasonal collection' : 'Featured collection'),
            'heading' => $placement->heading ?: $collection->name,
            'description' => $placement->description ?: $collection->description,
            'call_to_action' => $placement->call_to_action ?: 'Explore collection',
            'source' => $source,
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
                'description' => $collection->description,
                'assets_count' => $assetCount,
                'cover_image_url' => $coverUrl,
            ],
            'href' => route('collections.show', [
                'collection' => $collection->slug,
                'discovery_source' => $source,
                'placement_id' => $placement->id,
            ]),
        ];
    }
}
