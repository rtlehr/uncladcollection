<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Collection;
use App\Models\Image;
use App\Models\MarketingCampaign;
use App\Services\TrendingAssetService;
use App\Services\PersonalizedRecommendationService;
use App\Services\DiscoveryCollectionPlacementService;
use App\Services\HomepageDiscoveryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $settings = public_site_settings();
        $homepageSettings = $settings['homepage'] ?? [];

        $heroImageId = $this->integerSetting(
            Arr::get($homepageSettings, 'hero_image_id'),
        );

        $featuredCollectionIds = $this->integerList(
            Arr::get(
                $homepageSettings,
                'featured_collection_ids',
                [],
            ),
        );

        $featuredImageIds = $this->integerList(
            Arr::get(
                $homepageSettings,
                'featured_image_ids',
                [],
            ),
        );

        $heroImage = $this->getHeroImage($heroImageId);

        $heroCampaign = MarketingCampaign::query()
            ->current()
            ->orderBy('sort_order')
            ->latest()
            ->first();

        $featuredCollections = $this->getFeaturedCollections(
            $featuredCollectionIds,
        );

        $featuredImages = $this->getFeaturedImages(
            $featuredImageIds,
        );

        $latestArticles = $this->getLatestArticles();

        $discoveryCollectionPlacements = app(DiscoveryCollectionPlacementService::class)
            ->homepage(auth()->check());

        $recommendedAssets = auth()->check()
            ? app(PersonalizedRecommendationService::class)
                ->forUser(auth()->user(), 12)
                ->all()
            : [];

        $trendingAssets = app(TrendingAssetService::class)
            ->assets('week', 12)
            ->all();

        $homepageDiscoverySections = app(HomepageDiscoveryService::class)->compose(
            auth()->check(),
            [
                'primary_collections' => $discoveryCollectionPlacements['homepage_primary'] ?? [],
                'recommended' => $recommendedAssets,
                'trending' => $trendingAssets,
                'featured_assets' => $featuredImages,
                'secondary_collections' => $discoveryCollectionPlacements['homepage_secondary'] ?? [],
            ],
        );

        return Inertia::render('Welcome', [
            'siteSettings' => $settings,

            'heroImage' => $heroImage
                ? $this->formatHeroImage($heroImage)
                : null,

            'heroCampaign' => $heroCampaign ? [
                'id' => $heroCampaign->id,
                'name' => $heroCampaign->name,
                'media_type' => $heroCampaign->media_type,
                'media_url' => $heroCampaign->media_url,
                'poster_url' => $heroCampaign->poster_url,
                'eyebrow' => $heroCampaign->eyebrow,
                'headline' => $heroCampaign->headline,
                'subheadline' => $heroCampaign->subheadline,
                'primary_button_label' => $heroCampaign->primary_button_label,
                'primary_button_url' => $heroCampaign->primary_button_url,
                'secondary_button_label' => $heroCampaign->secondary_button_label,
                'secondary_button_url' => $heroCampaign->secondary_button_url,
                'overlay_opacity' => $heroCampaign->overlay_opacity,
                'media_position' => $heroCampaign->media_position,
                'hero_height' => $heroCampaign->hero_height,
                'text_alignment' => $heroCampaign->text_alignment,
                'autoplay_first_visit' => $heroCampaign->autoplay_first_visit,
                'autoplay_mobile' => $heroCampaign->autoplay_mobile,
                'loop_video' => $heroCampaign->loop_video,
                'show_search' => $heroCampaign->show_search,
            ] : null,

            'featuredCollections' => $featuredCollections,
            'discoveryCollectionPlacements' => $discoveryCollectionPlacements,

            'featuredImages' => $featuredImages,
            'recommendedAssets' => $recommendedAssets,
            'trendingAssets' => $trendingAssets,

            'homepageDiscoverySections' => $homepageDiscoverySections,

            'latestArticles' => $latestArticles,

            'statistics' => Cache::remember(
                'public.home.statistics',
                now()->addMinutes(5),
                fn () => [
                    'images' => Image::query()
                        ->where('is_active', true)
                        ->count(),

                    'collections' => Collection::query()
                        ->where('is_active', true)
                        ->count(),

                    'articles' => BlogPost::query()
                        ->published()
                        ->count(),

                    'downloads' => Image::query()
                        ->where('is_active', true)
                        ->sum('downloads_count'),
                ],
            ),
        ]);
    }

    private function getHeroImage(?int $heroImageId): ?Image
    {
        return Image::query()
            ->where('is_active', true)
            ->when(
                $heroImageId,
                function (Builder $query) use ($heroImageId) {
                    $query->whereKey($heroImageId);
                },
                function (Builder $query) {
                    $query
                        ->orderByDesc('purchases_count')
                        ->orderByDesc('favorites_count')
                        ->orderByDesc('views_count')
                        ->latest();
                },
            )
            ->first([
                'id',
                'title',
                'slug',
                'description',
                'photographer',
                'high_res_path',
                'thumbnail_path',
                'icon_path',
            ]);
    }

    private function getFeaturedCollections(array $collectionIds): array
    {
        $query = Collection::query()
            ->where('is_active', true)
            ->withCount([
                'images as images_count' => function (Builder $query) {
                    $query->where('is_active', true);
                },
            ])
            ->with([
                'images' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderByDesc('purchases_count')
                        ->orderByDesc('favorites_count')
                        ->orderByDesc('views_count')
                        ->select([
                            'id',
                            'collection_id',
                            'title',
                            'slug',
                            'thumbnail_path',
                            'icon_path',
                        ]);
                },
            ]);

        if ($collectionIds !== []) {
            $query
                ->whereKey($collectionIds)
                ->orderByRaw(
                    'FIELD(id, '.implode(',', $collectionIds).')',
                );
        } else {
            $query
                ->orderBy('sort_order')
                ->orderBy('name');
        }

        return $query
            ->limit(6)
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'cover_image_path',
                'sort_order',
            ])
            ->map(function (Collection $collection) {
                $fallbackImage = $collection->images->first();

                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'slug' => $collection->slug,
                    'description' => $collection->description,
                    'images_count' => $collection->images_count,

                    'cover_image' => $collection->cover_image_url
                        ? [
                            'title' => $collection->name.' collection cover',
                            'slug' => $collection->slug,
                            'thumbnail_url' => $collection->cover_image_url,
                            'icon_url' => null,
                        ]
                        : ($fallbackImage
                            ? [
                                'id' => $fallbackImage->id,
                                'title' => $fallbackImage->title,
                                'slug' => $fallbackImage->slug,
                                'thumbnail_url' => $fallbackImage->thumbnail_url,
                                'icon_url' => $fallbackImage->icon_url,
                            ]
                            : null),
                ];
            })
            ->values()
            ->all();
    }

    private function getFeaturedImages(array $imageIds): array
    {
        $query = Image::query()
            ->where('is_active', true)
            ->with([
                'collection:id,name,slug',
            ]);

        if ($imageIds !== []) {
            $query
                ->whereKey($imageIds)
                ->orderByRaw(
                    'FIELD(id, '.implode(',', $imageIds).')',
                );
        } else {
            $query
                ->orderByDesc('purchases_count')
                ->orderByDesc('favorites_count')
                ->orderByDesc('views_count')
                ->latest();
        }

        return $query
            ->limit(8)
            ->get([
                'id',
                'collection_id',
                'title',
                'slug',
                'photographer',
                'thumbnail_path',
                'icon_path',
                'is_ai_generated',
                'favorites_count',
                'downloads_count',
                'purchases_count',
                'views_count',
            ])
            ->map(function (Image $image) {
                return [
                    'id' => $image->id,
                    'title' => $image->title,
                    'slug' => $image->slug,
                    'photographer' => $image->photographer,
                    'thumbnail_url' => $image->thumbnail_url,
                    'icon_url' => $image->icon_url,
                    'is_ai_generated' => $image->is_ai_generated,
                    'favorites_count' => $image->favorites_count,

                    'collection' => $image->collection
                        ? [
                            'id' => $image->collection->id,
                            'name' => $image->collection->name,
                            'slug' => $image->collection->slug,
                        ]
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    private function getLatestArticles(): array
    {
        return BlogPost::query()
            ->published()
            ->with([
                'author:id,name',
                'categories:id,name,slug',
            ])
            ->latest('published_at')
            ->limit(3)
            ->get([
                'id',
                'user_id',
                'title',
                'slug',
                'excerpt',
                'featured_image_path',
                'header_image_path',
                'icon_image_path',
                'published_at',
                'is_featured',
            ])
            ->map(function (BlogPost $article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'excerpt' => $article->excerpt,
                    'featured_image_url' => $article->featured_image_url,
                    'header_image_url' => $article->header_image_url,
                    'icon_image_url' => $article->icon_image_url,
                    'published_at' => $article->published_at?->toISOString(),
                    'is_featured' => $article->is_featured,

                    'author' => $article->author
                        ? [
                            'id' => $article->author->id,
                            'name' => $article->author->name,
                        ]
                        : null,

                    'categories' => $article->categories
                        ->map(fn ($category) => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function formatHeroImage(Image $image): array
    {
        return [
            'id' => $image->id,
            'title' => $image->title,
            'slug' => $image->slug,
            'description' => $image->description,
            'photographer' => $image->photographer,
            'image_url' => $image->high_res_url
                ?? $image->thumbnail_url
                ?? $image->icon_url,
        ];
    }

    private function integerSetting(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $integer = filter_var(
            $value,
            FILTER_VALIDATE_INT,
        );

        if ($integer === false || $integer <= 0) {
            return null;
        }

        return $integer;
    }

    /**
     * Convert a JSON array, comma-separated string, or PHP array
     * into a clean list of unique positive integers.
     *
     * @return array<int>
     */
    private function integerList(mixed $value): array
    {
        if (is_string($value)) {
            $trimmedValue = trim($value);

            if ($trimmedValue === '') {
                return [];
            }

            $decodedValue = json_decode(
                $trimmedValue,
                true,
            );

            $value = is_array($decodedValue)
                ? $decodedValue
                : explode(',', $trimmedValue);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(function (mixed $item) {
                return filter_var(
                    $item,
                    FILTER_VALIDATE_INT,
                );
            })
            ->filter(function (mixed $item) {
                return $item !== false && $item > 0;
            })
            ->map(function (int $item) {
                return $item;
            })
            ->unique()
            ->values()
            ->all();
    }
}