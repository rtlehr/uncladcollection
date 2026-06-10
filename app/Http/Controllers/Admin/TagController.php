<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['name', 'tag_type', 'created_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        $tags = Tag::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query, $type) {
                $query->where('tag_type', $type);
            })
            ->orderBy($sort, $direction)
            ->get();

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'tagTypes' => [
                'image' => 'Image',
                'blog' => 'Blog',
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tags/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tag_type' => ['required', 'in:image,blog'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Tag::create($validated);

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    public function edit(Tag $tag)
    {
        return Inertia::render('Admin/Tags/Edit', [
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tag_type' => ['required', 'in:image,blog'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $tag->update($validated);

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag deleted successfully.');
    }
}