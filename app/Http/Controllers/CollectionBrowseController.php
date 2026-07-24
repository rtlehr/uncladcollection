<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Collection;
use App\Models\Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CollectionBrowseController extends Controller
{
    public function show(
        Request $request,
        Collection $collection,
    ): Response {
        abort_unless($collection->is_active, 404);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:curated,newest,oldest,most_viewed,most_favorited,most_downloaded'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'curated');

        $collection->loadCount([
            'images as images_count' => fn (Builder $query) => $query
                ->where('is_active', true),
        ]);

        $images = $this->imageCardQuery()
            ->where('is_active', true)
            ->where('collection_id', $collection->id)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('photographer', 'like', "%{$search}%")
                        ->orWhereHas(
                            'categories',
                            fn (Builder $query) => $query
                                ->where('name', 'like', "%{$search}%"),
                        )
                        ->orWhereHas(
                            'tags',
                            fn (Builder $query) => $query
                                ->where('name', 'like', "%{$search}%"),
                        );
                });
            })
            ->when(
                $sort === 'curated',
                fn (Builder $query) => $query
                    ->orderBy('sort_order')
                    ->orderByDesc('favorites_count')
                    ->orderByDesc('views_count'),
            )
            ->when(
                $sort === 'newest',
                fn (Builder $query) => $query->latest(),
            )
            ->when(
                $sort === 'oldest',
                fn (Builder $query) => $query->oldest(),
            )
            ->when(
                $sort === 'most_viewed',
                fn (Builder $query) => $query
                    ->orderByDesc('views_count'),
            )
            ->when(
                $sort === 'most_favorited',
                fn (Builder $query) => $query
                    ->orderByDesc('favorites_count'),
            )
            ->when(
                $sort === 'most_downloaded',
                fn (Builder $query) => $query
                    ->orderByDesc('downloads_count'),
            )
            ->paginate(24)
            ->withQueryString()
            ->through(
                fn (Image $image) => $this->formatImageCard($image),
            );

        $heroImages = Image::query()
            ->where('is_active', true)
            ->where('collection_id', $collection->id)
            ->orderBy('sort_order')
            ->orderByDesc('favorites_count')
            ->orderByDesc('views_count')
            ->limit(5)
            ->get([
                'id',
                'title',
                'slug',
                'thumbnail_path',
                'high_res_path',
                'icon_path',
            ])
            ->map(fn (Image $image) => [
                'id' => $image->id,
                'title' => $image->title,
                'slug' => $image->slug,
                'image_url' => $image->high_res_url
                    ?? $image->thumbnail_url
                    ?? $image->icon_url,
            ])
            ->values();

        $statisticsRow = Image::query()
            ->where('collection_id', $collection->id)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as images')
            ->selectRaw('COALESCE(SUM(views_count), 0) as views')
            ->selectRaw('COALESCE(SUM(favorites_count), 0) as favorites')
            ->selectRaw('COALESCE(SUM(downloads_count), 0) as downloads')
            ->first();

        $statistics = [
            'images' => (int) ($statisticsRow?->images ?? 0),
            'views' => (int) ($statisticsRow?->views ?? 0),
            'favorites' => (int) ($statisticsRow?->favorites ?? 0),
            'downloads' => (int) ($statisticsRow?->downloads ?? 0),
        ];

        $categoryIds = Image::query()
            ->where('collection_id', $collection->id)
            ->where('is_active', true)
            ->with('categories:id')
            ->get(['id'])
            ->flatMap(fn (Image $image) => $image->categories->modelKeys())
            ->unique()
            ->values();

        $tagIds = Image::query()
            ->where('collection_id', $collection->id)
            ->where('is_active', true)
            ->with('tags:id')
            ->get(['id'])
            ->flatMap(fn (Image $image) => $image->tags->modelKeys())
            ->unique()
            ->values();

        $relatedArticles = BlogPost::query()
            ->published()
            ->with([
                'author:id,name',
                'categories:id,name,slug',
                'tags:id,name,slug',
            ])
            ->where(function (Builder $query) use (
                $collection,
                $categoryIds,
                $tagIds,
            ) {
                $query
                    ->where('title', 'like', "%{$collection->name}%")
                    ->orWhere('excerpt', 'like', "%{$collection->name}%")
                    ->orWhere('content', 'like', "%{$collection->name}%");

                if ($categoryIds->isNotEmpty()) {
                    $query->orWhereHas(
                        'categories',
                        fn (Builder $query) => $query
                            ->whereKey($categoryIds->all()),
                    );
                }

                if ($tagIds->isNotEmpty()) {
                    $query->orWhereHas(
                        'tags',
                        fn (Builder $query) => $query
                            ->whereKey($tagIds->all()),
                    );
                }
            })
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->limit(3)
            ->get([
                'id',
                'user_id',
                'title',
                'slug',
                'excerpt',
                'content',
                'featured_image_path',
                'header_image_path',
                'icon_image_path',
                'published_at',
                'is_featured',
                'views_count',
            ]);

        $relatedCollections = Collection::query()
            ->where('is_active', true)
            ->whereKeyNot($collection->id)
            ->withCount([
                'images as images_count' => fn (Builder $query) => $query
                    ->where('is_active', true),
            ])
            ->whereHas('images', function (Builder $query) use (
                $categoryIds,
                $tagIds,
            ) {
                $query->where('is_active', true);

                if ($categoryIds->isNotEmpty()) {
                    $query->whereHas(
                        'categories',
                        fn (Builder $query) => $query
                            ->whereKey($categoryIds->all()),
                    );
                }

                if ($tagIds->isNotEmpty()) {
                    $query->orWhereHas(
                        'tags',
                        fn (Builder $query) => $query
                            ->whereKey($tagIds->all()),
                    );
                }
            })
            ->with([
                'images' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderByDesc('favorites_count')
                        ->limit(1)
                        ->select([
                            'id',
                            'collection_id',
                            'title',
                            'slug',
                            'thumbnail_path',
                            'icon_path',
                        ]);
                },
            ])
            ->orderBy('sort_order')
            ->limit(3)
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'cover_image_path',
                'sort_order',
            ])
            ->map(function (Collection $relatedCollection) {
                $fallbackImage = $relatedCollection->images->first();

                return [
                    'id' => $relatedCollection->id,
                    'name' => $relatedCollection->name,
                    'slug' => $relatedCollection->slug,
                    'description' => $relatedCollection->description,
                    'images_count' => $relatedCollection->images_count,
                    'cover_image_url' => $relatedCollection->cover_image_url
                        ?? ($fallbackImage
                            ? ($fallbackImage->thumbnail_url ?? $fallbackImage->icon_url)
                            : null),
                ];
            })
            ->values();

        return Inertia::render('Collections/Show', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
                'description' => $collection->description,
            ],
            'images' => $images,
            'heroImages' => $heroImages,
            'statistics' => $statistics,
            'relatedCollections' => $relatedCollections,
            'relatedArticles' => $relatedArticles,
            'suggestions' => $this->collectionSearchSuggestions(
                $collection,
                $search,
            ),

            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
        ]);
    }

    private function collectionSearchSuggestions(
        Collection $collection,
        string $search,
    ): array {
        if (mb_strlen($search) < 2) {
            return [];
        }

        $like = "%{$search}%";

        return collect()
            ->concat(
                Image::query()
                    ->where('collection_id', $collection->id)
                    ->where('is_active', true)
                    ->whereNotNull('photographer')
                    ->where('photographer', 'like', $like)
                    ->select('photographer')
                    ->distinct()
                    ->limit(4)
                    ->get()
                    ->map(fn (Image $item) => [
                        'type' => 'photographer',
                        'label' => $item->photographer,
                        'value' => $item->photographer,
                        'href' => null,
                        'meta' => 'Photographer in this collection',
                    ]),
            )
            ->concat(
                Image::query()
                    ->where('collection_id', $collection->id)
                    ->where('is_active', true)
                    ->whereHas(
                        'tags',
                        fn (Builder $query) => $query
                            ->where('name', 'like', $like),
                    )
                    ->with('tags:id,name')
                    ->limit(8)
                    ->get(['id'])
                    ->flatMap(fn (Image $image) => $image->tags)
                    ->filter(fn ($tag) => str_contains(
                        mb_strtolower($tag->name),
                        mb_strtolower($search),
                    ))
                    ->unique('id')
                    ->take(4)
                    ->map(fn ($tag) => [
                        'type' => 'tag',
                        'label' => $tag->name,
                        'value' => $tag->name,
                        'href' => null,
                        'meta' => 'Tag in this collection',
                    ]),
            )
            ->unique(fn (array $item) => $item['type'].'|'.$item['label'])
            ->take(8)
            ->values()
            ->all();
    }

    private function imageCardQuery(): Builder
    {
        $query = Image::query()
            ->select([
                'id',
                'collection_id',
                'title',
                'slug',
                'description',
                'photographer',
                'thumbnail_path',
                'icon_path',
                'is_ai_generated',
                'favorites_count',
                'downloads_count',
                'purchases_count',
                'views_count',
                'sort_order',
                'created_at',
            ])
            ->with([
                'collection:id,name,slug',
                'categories:id,name',
                'tags:id,name',
            ]);

        if (Auth::check()) {
            $query->withExists([
                'favorites as is_favorited' => fn (Builder $query) => $query
                    ->where('user_id', Auth::id()),
            ]);
        }

        return $query;
    }

    private function formatImageCard(Image $image): array
    {
        return [
            'id' => $image->id,
            'title' => $image->title,
            'slug' => $image->slug,
            'photographer' => $image->photographer,
            'thumbnail_url' => $image->thumbnail_path
                ? Storage::url($image->thumbnail_path)
                : null,
            'icon_url' => $image->icon_path
                ? Storage::url($image->icon_path)
                : null,
            'is_ai_generated' => $image->is_ai_generated,
            'is_favorited' => (bool) ($image->is_favorited ?? false),
            'favorites_count' => $image->favorites_count,
            'downloads_count' => $image->downloads_count,
            'purchases_count' => $image->purchases_count,
            'views_count' => $image->views_count,

            'collection' => $image->collection
                ? [
                    'id' => $image->collection->id,
                    'name' => $image->collection->name,
                    'slug' => $image->collection->slug,
                ]
                : null,

            'categories' => $image->categories
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values(),

            'tags' => $image->tags
                ->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])
                ->values(),
        ];
    }
}
