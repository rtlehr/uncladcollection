<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Image;
use App\Models\LicenseType;
use App\Models\Tag;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImageBrowseController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'collection_id' => ['nullable', 'integer', 'exists:collections,id'],
            'ai_generated' => ['nullable', 'in:0,1'],
            'sort' => ['nullable', 'in:newest,oldest,most_viewed,most_favorited,most_downloaded'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $categoryId = isset($validated['category_id'])
            ? (int) $validated['category_id']
            : null;
        $tagId = isset($validated['tag_id'])
            ? (int) $validated['tag_id']
            : null;
        $collectionId = isset($validated['collection_id'])
            ? (int) $validated['collection_id']
            : null;
        $aiGenerated = (string) ($validated['ai_generated'] ?? '');
        $sort = (string) ($validated['sort'] ?? 'newest');

        if (! in_array($sort, [
            'newest',
            'oldest',
            'most_viewed',
            'most_favorited',
            'most_downloaded',
        ], true)) {
            $sort = 'newest';
        }

        $images = $this->imageCardQuery()
            ->where('is_active', true)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('photographer', 'like', "%{$search}%")
                        ->orWhereHas(
                            'categories',
                            fn (Builder $query) => $query->where('name', 'like', "%{$search}%"),
                        )
                        ->orWhereHas(
                            'tags',
                            fn (Builder $query) => $query->where('name', 'like', "%{$search}%"),
                        )
                        ->orWhereHas(
                            'collection',
                            fn (Builder $query) => $query->where('name', 'like', "%{$search}%"),
                        );
                });
            })
            ->when(
                $categoryId,
                fn (Builder $query) => $query->whereHas(
                    'categories',
                    fn (Builder $query) => $query->whereKey($categoryId),
                ),
            )
            ->when(
                $tagId,
                fn (Builder $query) => $query->whereHas(
                    'tags',
                    fn (Builder $query) => $query->whereKey($tagId),
                ),
            )
            ->when(
                $collectionId,
                fn (Builder $query) => $query->where('collection_id', $collectionId),
            )
            ->when(
                in_array($aiGenerated, ['0', '1'], true),
                fn (Builder $query) => $query->where(
                    'is_ai_generated',
                    $aiGenerated === '1',
                ),
            )
            ->when($sort === 'oldest', fn (Builder $query) => $query->oldest())
            ->when($sort === 'most_viewed', fn (Builder $query) => $query->orderByDesc('views_count'))
            ->when($sort === 'most_favorited', fn (Builder $query) => $query->orderByDesc('favorites_count'))
            ->when($sort === 'most_downloaded', fn (Builder $query) => $query->orderByDesc('downloads_count'))
            ->when($sort === 'newest', fn (Builder $query) => $query->latest())
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Image $image) => $this->formatImageCard($image));

        return Inertia::render('Images/Index', [
            'images' => $images,

            'collections' => Collection::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),

            'categories' => Category::query()
                ->where('category_type', 'image')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'tags' => Tag::query()
                ->where('tag_type', 'image')
                ->orderBy('name')
                ->get(['id', 'name']),

            'suggestions' => $this->imageSearchSuggestions($search),

            'filters' => [
                'search' => $search,
                'category_id' => $categoryId ? (string) $categoryId : '',
                'tag_id' => $tagId ? (string) $tagId : '',
                'collection_id' => $collectionId ? (string) $collectionId : '',
                'ai_generated' => $aiGenerated,
                'sort' => $sort,
            ],
        ]);
    }

    private function imageSearchSuggestions(string $search): array
    {
        if (mb_strlen($search) < 2) {
            return [];
        }

        $like = "%{$search}%";

        return collect()
            ->concat(
                Category::query()
                    ->where('category_type', 'image')
                    ->where('is_active', true)
                    ->where('name', 'like', $like)
                    ->limit(3)
                    ->get(['id', 'name'])
                    ->map(fn (Category $item) => [
                        'type' => 'category',
                        'label' => $item->name,
                        'value' => $item->name,
                        'href' => "/images?category_id={$item->id}",
                        'meta' => 'Image category',
                    ]),
            )
            ->concat(
                Tag::query()
                    ->where('tag_type', 'image')
                    ->where('name', 'like', $like)
                    ->limit(3)
                    ->get(['id', 'name'])
                    ->map(fn (Tag $item) => [
                        'type' => 'tag',
                        'label' => $item->name,
                        'value' => $item->name,
                        'href' => "/images?tag_id={$item->id}",
                        'meta' => 'Image tag',
                    ]),
            )
            ->concat(
                Collection::query()
                    ->where('is_active', true)
                    ->where('name', 'like', $like)
                    ->limit(3)
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Collection $item) => [
                        'type' => 'collection',
                        'label' => $item->name,
                        'value' => $item->name,
                        'href' => "/collections/{$item->slug}",
                        'meta' => 'Collection',
                    ]),
            )
            ->concat(
                Image::query()
                    ->where('is_active', true)
                    ->whereNotNull('photographer')
                    ->where('photographer', 'like', $like)
                    ->select('photographer')
                    ->distinct()
                    ->limit(3)
                    ->get()
                    ->map(fn (Image $item) => [
                        'type' => 'photographer',
                        'label' => $item->photographer,
                        'value' => $item->photographer,
                        'href' => null,
                        'meta' => 'Photographer',
                    ]),
            )
            ->unique(fn (array $item) => $item['type'].'|'.$item['label'])
            ->take(8)
            ->values()
            ->all();
    }

    public function show(Image $image): Response
    {
        abort_unless($image->is_active, 404);

        $image->load([
            'collection:id,name,slug,description',
            'categories:id,name',
            'tags:id,name',
        ]);

        $image->increment('views_count');
        $image->views_count++;

        $user = Auth::user();

        $activeLicense = $user
            ? app(PurchaseService::class)->getActiveLicenseForImage(
                $user,
                $image,
            )
            : null;

        $previousImage = Image::query()
            ->where('is_active', true)
            ->where('id', '<', $image->id)
            ->orderByDesc('id')
            ->first([
                'id',
                'title',
                'slug',
            ]);

        $nextImage = Image::query()
            ->where('is_active', true)
            ->where('id', '>', $image->id)
            ->orderBy('id')
            ->first([
                'id',
                'title',
                'slug',
            ]);

        $relatedImages = $this->imageCardQuery()
            ->where('is_active', true)
            ->whereKeyNot($image->id)
            ->where(function (Builder $query) use ($image) {
                $categoryIds = $image->categories->modelKeys();
                $tagIds = $image->tags->modelKeys();

                if ($image->collection_id) {
                    $query->where('collection_id', $image->collection_id);
                } else {
                    $query->whereRaw('1 = 0');
                }

                if ($categoryIds !== []) {
                    $query->orWhereHas(
                        'categories',
                        fn (Builder $query) => $query->whereKey($categoryIds),
                    );
                }

                if ($tagIds !== []) {
                    $query->orWhereHas(
                        'tags',
                        fn (Builder $query) => $query->whereKey($tagIds),
                    );
                }
            })
            ->orderByDesc('favorites_count')
            ->orderByDesc('views_count')
            ->limit(8)
            ->get()
            ->map(fn (Image $relatedImage) => $this->formatImageCard($relatedImage))
            ->values();

        return Inertia::render('Images/Show', [
            'imageRecord' => [
                ...$this->formatImageCard($image),
                'description' => $image->description,
                'original_url' => $image->original_path
                    ? Storage::url($image->original_path)
                    : null,
                'high_res_url' => $image->high_res_path
                    ? Storage::url($image->high_res_path)
                    : null,
                'created_at' => $image->created_at?->format('Y-m-d'),
                'is_purchased' => $activeLicense !== null,
                'can_purchase' => $activeLicense === null,
                'can_download' => $activeLicense?->canDownload() ?? false,
                'active_license' => $activeLicense
                    ? [
                        'id' => $activeLicense->id,
                        'license_name' => $activeLicense->license_name,
                        'status' => $activeLicense->status,
                        'downloads_used' => $activeLicense->downloads_used,
                        'download_limit' => $activeLicense->download_limit,
                        'expires_at' => $activeLicense->expires_at?->format('Y-m-d'),
                    ]
                    : null,
            ],

            'licenseTypes' => LicenseType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'description',
                    'price_cents',
                    'currency',
                    'download_limit',
                    'expires_after_days',
                    'max_resolution',
                ]),

            'relatedImages' => $relatedImages,

            'previousImage' => $previousImage
                ? [
                    'id' => $previousImage->id,
                    'title' => $previousImage->title,
                    'slug' => $previousImage->slug,
                ]
                : null,

            'nextImage' => $nextImage
                ? [
                    'id' => $nextImage->id,
                    'title' => $nextImage->title,
                    'slug' => $nextImage->slug,
                ]
                : null,
        ]);
    }

    public function favorites(): Response
    {
        $images = $this->imageCardQuery()
            ->where('is_active', true)
            ->whereHas(
                'favorites',
                fn (Builder $query) => $query->where('user_id', Auth::id()),
            )
            ->latest()
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Image $image) => $this->formatImageCard($image));

        return Inertia::render('Images/Favorites', [
            'images' => $images,
        ]);
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
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values(),

            'tags' => $image->tags
                ->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])
                ->values(),
        ];
    }
}
