<?php

namespace App\Services;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Tag;
use App\Models\UserRecentSearch;
use App\Services\SearchIntelligence\SearchTermResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicSearchSuggestionService
{
    public function suggestions(string $term, ?int $userId = null): array
    {
        $normalized = $this->normalize($term);

        if ($normalized === '') {
            return collect($this->recentSearches($userId))
                ->concat($this->popularSearches())
                ->unique(fn (array $item) => $item['type'].'|'.$item['value'])
                ->take((int) config('discovery.suggestions.limit', 8))
                ->values()
                ->all();
        }

        if (mb_strlen($normalized) < (int) config('discovery.search.minimum_term_length', 2)) {
            return [];
        }

        $resolved = app(SearchTermResolver::class)->resolve($normalized);
        $version = app(DiscoveryCacheService::class)->version();
        $key = "discovery:suggestions:v{$version}:".sha1($normalized);

        return Cache::remember($key, now()->addMinutes((int) config('discovery.suggestions.cache_minutes', 10)), function () use ($normalized, $resolved): array {
            $like = "%{$normalized}%";
            $prefix = "{$normalized}%";
            $correction = $resolved['canonical'] !== $normalized ? [[
                'type' => 'correction',
                'label' => $resolved['canonical'],
                'value' => $resolved['canonical'],
                'href' => '/images?search='.rawurlencode($resolved['canonical']).'&sort=relevance',
                'meta' => 'Suggested correction',
            ]] : [];

            return collect($correction)->concat([
                [
                    'type' => 'term',
                    'label' => $normalized,
                    'value' => $normalized,
                    'href' => '/images?search='.rawurlencode($normalized).'&sort=relevance',
                    'meta' => 'Search all assets',
                ],
            ])
                ->concat(Category::query()
                    ->where('category_type', 'image')
                    ->where('is_active', true)
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orderByRaw('CASE WHEN LOWER(name) LIKE ? THEN 0 ELSE 1 END', [$prefix])
                    ->orderBy('name')
                    ->limit(2)
                    ->get(['id', 'name'])
                    ->map(fn (Category $item) => [
                        'type' => 'category', 'label' => $item->name, 'value' => $item->name,
                        'href' => "/images?category_id={$item->id}", 'meta' => 'Category',
                    ]))
                ->concat(Tag::query()
                    ->where('tag_type', 'image')
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orderByRaw('CASE WHEN LOWER(name) LIKE ? THEN 0 ELSE 1 END', [$prefix])
                    ->orderBy('name')
                    ->limit(2)
                    ->get(['id', 'name'])
                    ->map(fn (Tag $item) => [
                        'type' => 'tag', 'label' => $item->name, 'value' => $item->name,
                        'href' => "/images?tag_id={$item->id}", 'meta' => 'Tag',
                    ]))
                ->concat(Collection::query()
                    ->where('is_active', true)
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orderByRaw('CASE WHEN LOWER(name) LIKE ? THEN 0 ELSE 1 END', [$prefix])
                    ->orderBy('name')
                    ->limit(2)
                    ->get(['name', 'slug'])
                    ->map(fn (Collection $item) => [
                        'type' => 'collection', 'label' => $item->name, 'value' => $item->name,
                        'href' => "/collections/{$item->slug}", 'meta' => 'Collection',
                    ]))
                ->concat(Asset::query()
                    ->discoverable()
                    ->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orderByRaw('CASE WHEN LOWER(title) LIKE ? THEN 0 ELSE 1 END', [$prefix])
                    ->orderByDesc('is_featured')
                    ->limit(3)
                    ->get(['title', 'slug', 'legacy_image_id'])
                    ->map(fn (Asset $item) => [
                        'type' => 'asset', 'label' => $item->title, 'value' => $item->title,
                        'href' => $item->legacy_image_id ? "/images/{$item->slug}" : "/assets/{$item->slug}", 'meta' => 'Asset',
                    ]))
                ->concat(Asset::query()
                    ->discoverable()
                    ->whereNotNull('photographer')
                    ->whereRaw('LOWER(photographer) LIKE ?', [$like])
                    ->select('photographer')
                    ->distinct()
                    ->orderBy('photographer')
                    ->limit(2)
                    ->get()
                    ->map(fn (Asset $item) => [
                        'type' => 'creator', 'label' => $item->photographer, 'value' => $item->photographer,
                        'href' => '/images?search='.rawurlencode((string) $item->photographer).'&sort=relevance', 'meta' => 'Creator',
                    ]))
                ->unique(fn (array $item) => $item['type'].'|'.mb_strtolower($item['label']))
                ->take((int) config('discovery.suggestions.limit', 8))
                ->values()
                ->all();
        });
    }

    public function rememberSearch(int $userId, string $term): void
    {
        $normalized = $this->normalize($term);
        if ($normalized === '') return;

        UserRecentSearch::query()->updateOrCreate(
            ['user_id' => $userId, 'normalized_term' => $normalized],
            ['term' => trim($term), 'searched_at' => now()],
        );

        UserRecentSearch::query()
            ->where('user_id', $userId)
            ->whereNotIn('id', UserRecentSearch::query()->where('user_id', $userId)->latest('searched_at')->limit(10)->pluck('id'))
            ->delete();
    }

    private function recentSearches(?int $userId): array
    {
        if (! $userId) return [];

        return UserRecentSearch::query()
            ->where('user_id', $userId)
            ->latest('searched_at')
            ->limit(4)
            ->get()
            ->map(fn (UserRecentSearch $item) => [
                'type' => 'recent', 'label' => $item->term, 'value' => $item->term,
                'href' => '/images?search='.rawurlencode($item->term).'&sort=relevance', 'meta' => 'Recent search',
            ])->all();
    }

    private function popularSearches(): array
    {
        $version = app(DiscoveryCacheService::class)->version();

        return Cache::remember("discovery:popular-searches:v{$version}", now()->addMinutes((int) config('discovery.suggestions.popular_cache_minutes', 30)), function (): array {
            return AnalyticsEvent::query()
                ->where('event_name', AnalyticsEventName::SearchPerformed->value)
                ->where('occurred_at', '>=', now()->subDays(30))
                ->latest('occurred_at')
                ->limit(1000)
                ->get(['dimensions'])
                ->map(fn (AnalyticsEvent $event) => trim((string) data_get($event->dimensions, 'term')))
                ->filter(fn (string $term) => $term !== '')
                ->countBy()
                ->sortDesc()
                ->take(4)
                ->map(fn (int $count, string $term) => [
                    'type' => 'popular', 'label' => $term, 'value' => $term,
                    'href' => '/images?search='.rawurlencode($term).'&sort=relevance', 'meta' => 'Popular search',
                ])->values()->all();
        });
    }

    private function normalize(string $term): string
    {
        return Str::of($term)->lower()->replaceMatches('/[^\pL\pN]+/u', ' ')->squish()->limit(120, '')->toString();
    }
}
