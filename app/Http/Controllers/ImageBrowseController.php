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
        $search = trim($request->string('search')->toString());
        $categoryId = $request->integer('category_id') ?: null;
        $tagId = $request->integer('tag_id') ?: null;
        $collectionId = $request->integer('collection_id') ?: null;
        $aiGenerated = $request->string('ai_generated')->toString();
        $sort = $request->string('sort', 'newest')->toString();

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

    public function show(Image $image): Response
    {
        abort_unless($image->is_active, 404);

        $image->load([
            'collection:id,name',
            'categories:id,name',
            'tags:id,name',
        ]);

        $image->increment('views_count');

        $user = Auth::user();
        $activeLicense = $user
            ? app(PurchaseService::class)->getActiveLicenseForImage($user, $image)
            : null;

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
                'is_favorited' => $user
                    ? $image->favorites()
                        ->where('user_id', $user->id)
                        ->exists()
                    : false,
                'is_purchased' => $activeLicense !== null,
                'can_purchase' => $activeLicense === null,
                'can_download' => $activeLicense?->canDownload() ?? false,
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
                ]),

            'relatedImages' => $this->imageCardQuery()
                ->where('is_active', true)
                ->whereKeyNot($image->id)
                ->where(function (Builder $query) use ($image) {
                    $categoryIds = $image->categories->modelKeys();

                    $query->where('collection_id', $image->collection_id);

                    if ($categoryIds !== []) {
                        $query->orWhereHas(
                            'categories',
                            fn (Builder $query) => $query->whereKey($categoryIds),
                        );
                    }
                })
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Image $relatedImage) => $this->formatImageCard($relatedImage))
                ->values(),
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
        return Image::query()
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
                'collection:id,name',
                'categories:id,name',
                'tags:id,name',
            ]);
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
            'favorites_count' => $image->favorites_count,
            'downloads_count' => $image->downloads_count,
            'purchases_count' => $image->purchases_count,
            'views_count' => $image->views_count,

            'collection' => $image->collection
                ? [
                    'id' => $image->collection->id,
                    'name' => $image->collection->name,
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
