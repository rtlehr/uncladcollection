<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Image;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $collectionId = $request->string('collection_id')->toString();
        $sort = $request->string('sort', 'sort_order')->toString();
        $direction = $request->string('direction', 'asc')->toString();

        $allowedSorts = [
            'title',
            'slug',
            'photographer',
            'sort_order',
            'is_active',
            'created_at',
        ];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'sort_order';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $images = Image::query()
            ->with(['collection', 'categories', 'tags'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('photographer', 'like', "%{$search}%");
                });
            })
            ->when($collectionId !== '', function ($query) use ($collectionId) {
                $query->where('collection_id', $collectionId);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('is_active', (bool) $status);
            })
            ->orderBy($sort, $direction)
            ->get()
            ->map(fn (Image $image) => $this->formatImageForIndex($image));

        return Inertia::render('Admin/Images/Index', [
            'images' => $images,

            'collections' => Collection::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),

            'filters' => [
                'search' => $search,
                'status' => $status,
                'collection_id' => $collectionId,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Images/Create', [
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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'collection_id' => ['nullable', 'exists:collections,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'photographer' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],

            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:51200'],

            'categories' => ['array'],
            'categories.*' => ['exists:categories,id'],

            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $image = Image::create([
            'collection_id' => $validated['collection_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title']),
            'description' => $validated['description'] ?? null,
            'photographer' => $validated['photographer'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $paths = $this->storeImageVersions($request, $image);

        $image->update($paths);

        $image->categories()->sync($validated['categories'] ?? []);
        $image->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image created successfully.');
    }

    public function edit(Image $image): Response
    {
        $image->load(['categories', 'tags']);

        return Inertia::render('Admin/Images/Edit', [
            'imageRecord' => $this->formatImageForEdit($image),

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
        ]);
    }

    public function update(Request $request, Image $image): RedirectResponse
    {
        $validated = $request->validate([
            'collection_id' => ['nullable', 'exists:collections,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'photographer' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],

            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:51200'],

            'categories' => ['array'],
            'categories.*' => ['exists:categories,id'],

            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $image->update([
            'collection_id' => $validated['collection_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $image->id),
            'description' => $validated['description'] ?? null,
            'photographer' => $validated['photographer'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImageFolder($image);
            $image->update($this->storeImageVersions($request, $image));
        }

        $image->categories()->sync($validated['categories'] ?? []);
        $image->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image updated successfully.');
    }

    public function destroy(Image $image): RedirectResponse
    {
        $this->deleteImageFolder($image);

        $image->delete();

        return redirect()
            ->route('admin.images.index')
            ->with('success', 'Image deleted successfully.');
    }

    private function storeImageVersions(Request $request, Image $image): array
    {
        $uploadedFile = $request->file('image');

        $extension = $uploadedFile->getClientOriginalExtension();
        $baseFilename = Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));

        if ($baseFilename === '') {
            $baseFilename = 'image';
        }

        $filename = "{$baseFilename}.{$extension}";
        $baseFolder = $this->imageBaseFolder($image);

        $originalPath = $uploadedFile->storeAs(
            "{$baseFolder}/original",
            $filename,
            'public'
        );

        $highResPath = $uploadedFile->storeAs(
            "{$baseFolder}/high-res",
            $filename,
            'public'
        );

        $thumbnailPath = $uploadedFile->storeAs(
            "{$baseFolder}/thumbnail",
            $filename,
            'public'
        );

        $iconPath = $uploadedFile->storeAs(
            "{$baseFolder}/icon",
            $filename,
            'public'
        );

        return [
            'original_path' => $originalPath,
            'high_res_path' => $highResPath,
            'thumbnail_path' => $thumbnailPath,
            'icon_path' => $iconPath,
        ];
    }

    private function deleteImageFolder(Image $image): void
    {
        Storage::disk('public')->deleteDirectory($this->imageBaseFolder($image));
    }

    private function imageBaseFolder(Image $image): string
    {
        $collection = $image->collection;

        if (! $collection && $image->collection_id) {
            $collection = Collection::find($image->collection_id);
        }

        $collectionFolder = $collection
            ? "{$collection->id}-{$collection->slug}"
            : 'unassigned';

        return "images/{$collectionFolder}/{$image->id}";
    }

    private function formatImageForIndex(Image $image): array
    {
        return [
            'id' => $image->id,
            'title' => $image->title,
            'slug' => $image->slug,
            'description' => $image->description,

            'original_path' => $image->original_path,
            'original_url' => $image->original_path ? Storage::url($image->original_path) : null,

            'high_res_path' => $image->high_res_path,
            'high_res_url' => $image->high_res_path ? Storage::url($image->high_res_path) : null,

            'thumbnail_path' => $image->thumbnail_path,
            'thumbnail_url' => $image->thumbnail_path ? Storage::url($image->thumbnail_path) : null,

            'icon_path' => $image->icon_path,
            'icon_url' => $image->icon_path ? Storage::url($image->icon_path) : null,

            'photographer' => $image->photographer,
            'sort_order' => $image->sort_order,
            'is_active' => $image->is_active,

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

            'created_at' => $image->created_at?->format('Y-m-d'),
        ];
    }

    private function formatImageForEdit(Image $image): array
    {
        return [
            'id' => $image->id,
            'collection_id' => $image->collection_id,
            'title' => $image->title,
            'slug' => $image->slug,
            'description' => $image->description,

            'original_path' => $image->original_path,
            'original_url' => $image->original_path ? Storage::url($image->original_path) : null,

            'high_res_path' => $image->high_res_path,
            'high_res_url' => $image->high_res_path ? Storage::url($image->high_res_path) : null,

            'thumbnail_path' => $image->thumbnail_path,
            'thumbnail_url' => $image->thumbnail_path ? Storage::url($image->thumbnail_path) : null,

            'icon_path' => $image->icon_path,
            'icon_url' => $image->icon_path ? Storage::url($image->icon_path) : null,

            'photographer' => $image->photographer,
            'sort_order' => $image->sort_order,
            'is_active' => $image->is_active,

            'categories' => $image->categories->pluck('id')->values(),
            'tags' => $image->tags->pluck('id')->values(),
        ];
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (
            Image::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}