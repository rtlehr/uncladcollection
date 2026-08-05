<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RelatedAssetService
{
    public function __construct(
        private readonly DiscoveryCacheService $cache,
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function forAsset(Asset $asset, int $limit = 6): Collection
    {
        $key = $this->cache->key('related-assets', [
            'asset' => $asset->id,
            'updated' => $asset->updated_at?->timestamp ?? 0,
            'limit' => $limit,
        ]);

        $ttl = now()->addMinutes((int) config('discovery.related.cache_minutes', 30));
        $cached = Cache::get($key);

        // Older deployments cached the Collection object itself. If PHP cannot
        // safely unserialize that value, Laravel returns __PHP_Incomplete_Class.
        // Discard any incompatible cache value and rebuild it as a plain array.
        if ($cached !== null && ! is_array($cached)) {
            Cache::forget($key);
            $cached = null;
        }

        if ($cached === null) {
            $cached = $this->buildRelatedAssets($asset, $limit)->all();
            Cache::put($key, $cached, $ttl);
        }

        return collect($cached);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function buildRelatedAssets(Asset $asset, int $limit): Collection
    {
        $asset->loadMissing(['categories:id,name', 'tags:id,name', 'activeFiles', 'primaryPreviewFile', 'posterFile']);

        $candidateLimit = max($limit * 10, (int) config('discovery.related.candidate_limit', 120));
        $candidates = Asset::query()
            ->discoverable()
            ->whereKeyNot($asset->id)
            ->where(function ($query) use ($asset): void {
                $query->where('asset_type', $asset->asset_type->value)
                    ->when($asset->collection_id, fn ($query) => $query->orWhere('collection_id', $asset->collection_id))
                    ->when($asset->categories->isNotEmpty(), fn ($query) => $query->orWhereHas('categories', fn ($query) => $query->whereKey($asset->categories->modelKeys())))
                    ->when($asset->tags->isNotEmpty(), fn ($query) => $query->orWhereHas('tags', fn ($query) => $query->whereKey($asset->tags->modelKeys())));
            })
            ->with(['collection:id,name,slug', 'categories:id,name', 'tags:id,name', 'activeFiles', 'primaryPreviewFile', 'posterFile'])
            ->orderByDesc('is_featured')
            ->orderByDesc('views_count')
            ->limit($candidateLimit)
            ->get();

        $sourceCategories = $asset->categories->modelKeys();
        $sourceTags = $asset->tags->modelKeys();
        $sourceFormats = $asset->activeFiles->pluck('extension')->filter()->map(fn ($value) => strtolower($value))->unique();
        $sourceOrientation = $this->orientation($asset);

        return $candidates
            ->map(function (Asset $candidate) use ($asset, $sourceCategories, $sourceTags, $sourceFormats, $sourceOrientation): array {
                $categoryMatches = count(array_intersect($sourceCategories, $candidate->categories->modelKeys()));
                $tagMatches = count(array_intersect($sourceTags, $candidate->tags->modelKeys()));
                $formatMatches = $sourceFormats->intersect($candidate->activeFiles->pluck('extension')->filter()->map(fn ($value) => strtolower($value))->unique())->count();
                $sameCollection = $asset->collection_id && $candidate->collection_id === $asset->collection_id;
                $sameType = $candidate->asset_type === $asset->asset_type;
                $sameOrientation = $sourceOrientation && $sourceOrientation === $this->orientation($candidate);

                $score = ($sameCollection ? 32 : 0)
                    + ($categoryMatches * 18)
                    + ($tagMatches * 12)
                    + ($sameType ? 10 : 0)
                    + ($sameOrientation ? 5 : 0)
                    + (min($formatMatches, 3) * 3)
                    + ($candidate->is_featured ? 4 : 0)
                    + min(5, log10(max(1, $candidate->views_count + 1)));

                return [
                    'asset' => $candidate,
                    'score' => round($score, 3),
                    'reason' => $this->reason($sameCollection, $categoryMatches, $tagMatches, $sameType),
                ];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $ranked) => $this->format($ranked['asset'], $ranked['reason'], $ranked['score'], 'related_assets', $asset->id))
            ->values();
    }

    /** @return array<string, mixed> */
    public function format(Asset $asset, ?string $reason = null, ?float $score = null, string $source = 'related_assets', ?int $originAssetId = null): array
    {
        $preview = app(AssetPresentationService::class)->marketplaceUrl($asset)
            ?? ($asset->primaryPreviewFile ? app(AssetMediaPresentationService::class)->url($asset, $asset->primaryPreviewFile) : null)
            ?? ($asset->posterFile ? app(AssetMediaPresentationService::class)->url($asset, $asset->posterFile) : null);

        return [
            'id' => $asset->id,
            'title' => $asset->title,
            'slug' => $asset->slug,
            'href' => route('assets.show', array_filter(['asset' => $asset, 'discovery_source' => $source, 'origin_asset' => $originAssetId])),
            'asset_type' => $asset->asset_type->value,
            'asset_type_label' => $asset->asset_type->label(),
            'preview_url' => $preview,
            'formats' => $asset->activeFiles->pluck('extension')->filter()->map(fn ($ext) => strtoupper($ext))->unique()->values()->all(),
            'reason' => $reason,
            'relevance_score' => $score,
        ];
    }

    private function orientation(Asset $asset): ?string
    {
        $file = $asset->primaryPreviewFile ?? $asset->posterFile ?? $asset->activeFiles->first(fn ($file) => $file->width && $file->height);
        if (! $file?->width || ! $file?->height) return null;
        if ($file->width === $file->height) return 'square';
        return $file->width > $file->height ? 'landscape' : 'portrait';
    }

    private function reason(bool $sameCollection, int $categoryMatches, int $tagMatches, bool $sameType): string
    {
        return match (true) {
            $sameCollection => 'From the same collection',
            $categoryMatches > 0 => 'Similar subject',
            $tagMatches > 0 => 'Shared keywords',
            $sameType => 'Similar format',
            default => 'Popular related asset',
        };
    }
}
