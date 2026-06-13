<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Image;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\LicenseType;
use App\Services\PurchaseService;

class ImageBrowseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $categoryId = $request->string('category_id')->toString();
        $tagId = $request->string('tag_id')->toString();
        $collectionId = $request->string('collection_id')->toString();
        $aiGenerated = $request->string('ai_generated')->toString();
        $sort = $request->string('sort', 'newest')->toString();

        $images = Image::query()
            ->with(['collection', 'categories', 'tags'])
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('photographer', 'like', "%{$search}%")
                        ->orWhereHas('categories', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('tags', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('collection', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($categoryId !== '', function ($query) use ($categoryId) {
                $query->whereHas('categories', fn ($query) => $query->where('categories.id', $categoryId));
            })
            ->when($tagId !== '', function ($query) use ($tagId) {
                $query->whereHas('tags', fn ($query) => $query->where('tags.id', $tagId));
            })
            ->when($collectionId !== '', function ($query) use ($collectionId) {
                $query->where('collection_id', $collectionId);
            })
            ->when($aiGenerated !== '', function ($query) use ($aiGenerated) {
                $query->where('is_ai_generated', (bool) $aiGenerated);
            })
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort === 'most_viewed', fn ($query) => $query->orderByDesc('views_count'))
            ->when($sort === 'most_favorited', fn ($query) => $query->orderByDesc('favorites_count'))
            ->when($sort === 'most_downloaded', fn ($query) => $query->orderByDesc('downloads_count'))
            ->when($sort === 'newest', fn ($query) => $query->latest())
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
                ->orderBy('name')
                ->get(['id', 'name']),

            'tags' => Tag::query()
                ->where('tag_type', 'image')
                ->orderBy('name')
                ->get(['id', 'name']),

            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'tag_id' => $tagId,
                'collection_id' => $collectionId,
                'ai_generated' => $aiGenerated,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Image $image): Response
    {
        abort_unless($image->is_active, 404);

        $image->load(['collection', 'categories', 'tags']);

        $image->increment('views_count');

        $user = Auth::user();

        $isPurchased = false;
        $canDownload = false;

        if ($user) {
            $activeLicense = app(PurchaseService::class)
                ->getActiveLicenseForImage($user, $image);

            $isPurchased = $activeLicense !== null;
            $canDownload = $activeLicense?->canDownload() ?? false;
        }

        return Inertia::render('Images/Show', [
            'imageRecord' => array_merge(
                $this->formatImageCard($image->fresh(['collection', 'categories', 'tags'])),
                [
                    'description' => $image->description,
                    'original_url' => $image->original_path ? Storage::url($image->original_path) : null,
                    'high_res_url' => $image->high_res_path ? Storage::url($image->high_res_path) : null,
                    'created_at' => $image->created_at?->format('Y-m-d'),
                    'is_favorited' => $user
                        ? $image->favorites()->where('user_id', $user->id)->exists()
                        : false,
                    'is_purchased' => $isPurchased,
                    'can_purchase' => ! $isPurchased,
                    'can_download' => $canDownload,
                ]
            ),

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

            'relatedImages' => Image::query()
                ->with(['collection', 'categories', 'tags'])
                ->where('is_active', true)
                ->where('id', '!=', $image->id)
                ->where(function ($query) use ($image) {
                    $query->where('collection_id', $image->collection_id)
                        ->orWhereHas('categories', function ($query) use ($image) {
                            $query->whereIn('categories.id', $image->categories->pluck('id'));
                        });
                })
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Image $image) => $this->formatImageCard($image)),
        ]);
    }

    private function formatImageCard(Image $image): array
    {
        return [
            'id' => $image->id,
            'title' => $image->title,
            'slug' => $image->slug,
            'photographer' => $image->photographer,
            'thumbnail_url' => $image->thumbnail_path ? Storage::url($image->thumbnail_path) : null,
            'icon_url' => $image->icon_path ? Storage::url($image->icon_path) : null,
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

    public function favorites(): Response
    {
        $images = Image::query()
            ->with(['collection', 'categories', 'tags'])
            ->where('is_active', true)
            ->whereHas('favorites', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->paginate(24)
            ->through(fn (Image $image) => $this->formatImageCard($image));

        return Inertia::render('Images/Favorites', [
            'images' => $images,
        ]);
    }

}