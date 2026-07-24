<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Services\CollectionCoverMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(
        private readonly CollectionCoverMediaService $coverMediaService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $sort = $request->input('sort', 'sort_order');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['name', 'slug', 'sort_order', 'is_active', 'created_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'sort_order';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $collections = Collection::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query
                ->where('is_active', (bool) $status))
            ->orderBy($sort, $direction)
            ->get();

        return Inertia::render('Admin/Collections/Index', [
            'collections' => $collections,
            'filters' => compact('search', 'status', 'sort', 'direction'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Collections/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCollection($request);

        $collection = DB::transaction(function () use ($request, $validated) {
            $collection = Collection::create([
                ...$this->collectionAttributes($request, $validated),
                'slug' => Str::slug($validated['name']),
            ]);

            if ($request->hasFile('cover_original') && $request->hasFile('cover_image')) {
                $collection->update($this->storeNewCover($request, $collection));
            }

            return $collection;
        });

        return redirect()
            ->route('admin.collections.edit', $collection)
            ->with('success', 'Collection created successfully.');
    }

    public function edit(Collection $collection): Response
    {
        return Inertia::render('Admin/Collections/Edit', [
            'collection' => $collection,
        ]);
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $validated = $this->validateCollection($request);
        $oldOriginal = $collection->cover_original_path;
        $oldRendered = $collection->cover_image_path;

        DB::transaction(function () use (
            $request,
            $validated,
            $collection,
            $oldOriginal,
            $oldRendered,
        ) {
            $attributes = [
                ...$this->collectionAttributes($request, $validated),
                'slug' => Str::slug($validated['name']),
            ];

            if ($request->boolean('remove_cover_image')) {
                $attributes['cover_original_path'] = null;
                $attributes['cover_image_path'] = null;
                $attributes['cover_edit_data'] = null;
            } elseif ($request->hasFile('cover_image')) {
                $attributes['cover_image_path'] = $this->coverMediaService->storeRendered(
                    $request->file('cover_image'),
                    $collection->id,
                );
                $attributes['cover_edit_data'] = $this->decodeEditData(
                    $request->input('cover_edit_data'),
                );

                if ($request->hasFile('cover_original')) {
                    $attributes['cover_original_path'] = $this->coverMediaService->storeOriginal(
                        $request->file('cover_original'),
                        $collection->id,
                    );
                }
            }

            $collection->update($attributes);

            if ($request->boolean('remove_cover_image') || $request->hasFile('cover_image')) {
                $this->coverMediaService->delete($oldRendered);
            }

            if ($request->boolean('remove_cover_image') || $request->hasFile('cover_original')) {
                $this->coverMediaService->delete($oldOriginal);
            }
        });

        return redirect()
            ->route('admin.collections.edit', $collection)
            ->with('success', 'Collection updated successfully.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collectionId = $collection->id;
        $collection->delete();
        $this->coverMediaService->deleteCollectionDirectory($collectionId);

        return redirect()
            ->route('admin.collections.index')
            ->with('success', 'Collection deleted successfully.');
    }

    private function validateCollection(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'cover_original' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
            'cover_image' => ['nullable', 'required_with:cover_original', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'cover_edit_data' => ['nullable', 'json'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ]);
    }

    private function collectionAttributes(Request $request, array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function storeNewCover(Request $request, Collection $collection): array
    {
        return [
            'cover_original_path' => $this->coverMediaService->storeOriginal(
                $request->file('cover_original'),
                $collection->id,
            ),
            'cover_image_path' => $this->coverMediaService->storeRendered(
                $request->file('cover_image'),
                $collection->id,
            ),
            'cover_edit_data' => $this->decodeEditData(
                $request->input('cover_edit_data'),
            ),
        ];
    }

    private function decodeEditData(?string $value): ?array
    {
        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
