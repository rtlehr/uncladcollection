<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $sort = $request->input('sort', 'sort_order');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = [
            'name',
            'slug',
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

        $collections = Collection::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', (bool) $status);
            })
            ->orderBy($sort, $direction)
            ->get();

        return Inertia::render('Admin/Collections/Index', [
            'collections' => $collections,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Collections/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        Collection::create($validated);

        return redirect()
            ->route('admin.collections.index')
            ->with('success', 'Collection created successfully.');
    }

    public function edit(Collection $collection)
    {
        return Inertia::render('Admin/Collections/Edit', [
            'collection' => $collection,
        ]);
    }

    public function update(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $collection->update($validated);

        return redirect()
            ->route('admin.collections.index')
            ->with('success', 'Collection updated successfully.');
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();

        return redirect()
            ->route('admin.collections.index')
            ->with('success', 'Collection deleted successfully.');
    }
}