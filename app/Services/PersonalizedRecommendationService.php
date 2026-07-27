<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PersonalizedRecommendationService
{
    public function __construct(
        private readonly DiscoveryCacheService $cache,
        private readonly UserAssetAffinityService $affinities,
        private readonly RelatedAssetService $formatter,
        private readonly TrendingAssetService $trending,
    ) {}

    public function forUser(User $user, int $limit = 8): Collection
    {
        $key = $this->cache->key('recommendations', ['user' => $user->id, 'limit' => $limit]);
        $cached = Cache::get($key);
        if (is_array($cached)) return collect($cached);
        if ($cached !== null) Cache::forget($key);

        $profile = $this->affinities->forUser($user);
        if ($profile->isEmpty()) return $this->coldStart($limit);

        $scores = $profile->groupBy('dimension')->map(fn ($rows) => $rows->pluck('score', 'value'));
        $excluded = $user->recentlyViewedAssets()->latest('last_viewed_at')->limit(3)->pluck('asset_id');
        $favoriteIds = $user->assetFavorites()->pluck('asset_id');
        $excluded = $excluded->merge($favoriteIds)->unique();

        $assets = Asset::query()
            ->discoverable()
            ->whereNotIn('id', $excluded)
            ->with(['collection:id,name,slug', 'categories:id,name', 'tags:id,name', 'activeFiles', 'primaryPreviewFile', 'posterFile', 'trendingScores' => fn ($q) => $q->where('period', 'week')])
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit((int) config('discovery.recommendations.candidate_limit', 180))
            ->get()
            ->map(function (Asset $asset) use ($scores): array {
                $score = (float) ($scores->get('asset_type')?->get($asset->asset_type->value, 0) ?? 0);
                if ($asset->collection_id) $score += (float) ($scores->get('collection')?->get((string) $asset->collection_id, 0) ?? 0) * 1.2;
                foreach ($asset->categories as $category) $score += (float) ($scores->get('category')?->get((string) $category->id, 0) ?? 0) * 1.35;
                foreach ($asset->tags as $tag) $score += (float) ($scores->get('tag')?->get((string) $tag->id, 0) ?? 0);
                $score += min(8, ((float) optional($asset->trendingScores->first())->score) / 10);
                if ($asset->is_featured) $score += 3;
                return ['asset' => $asset, 'score' => $score];
            })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score');

        $chosen = collect();
        $collectionCounts = [];
        $typeCounts = [];
        $maxPerCollection = (int) config('discovery.recommendations.max_per_collection', 2);
        $maxPerType = (int) config('discovery.recommendations.max_per_asset_type', 4);

        foreach ($assets as $row) {
            $asset = $row['asset'];
            $collectionKey = $asset->collection_id ?: 'none';
            $typeKey = $asset->asset_type->value;
            if (($collectionCounts[$collectionKey] ?? 0) >= $maxPerCollection || ($typeCounts[$typeKey] ?? 0) >= $maxPerType) continue;
            $chosen->push($this->formatter->format($asset, 'Based on your interests', round($row['score'], 3), 'recommended_for_you'));
            $collectionCounts[$collectionKey] = ($collectionCounts[$collectionKey] ?? 0) + 1;
            $typeCounts[$typeKey] = ($typeCounts[$typeKey] ?? 0) + 1;
            if ($chosen->count() >= $limit) break;
        }

        if ($chosen->count() < $limit) {
            $fallback = $this->coldStart($limit * 2)->reject(fn (array $item) => $chosen->contains('id', $item['id']))->take($limit - $chosen->count());
            $chosen = $chosen->concat($fallback)->values();
        }

        Cache::put($key, $chosen->all(), now()->addMinutes((int) config('discovery.recommendations.cache_minutes', 30)));
        return $chosen;
    }

    private function coldStart(int $limit): Collection
    {
        return $this->trending->assets('week', $limit)->map(function (array $asset): array {
            $asset['reason'] = 'Popular with marketplace visitors';
            $asset['href'] = preg_replace('/discovery_source=[^&]+/', 'discovery_source=recommended_for_you', $asset['href']) ?? $asset['href'];
            return $asset;
        })->values();
    }
}
