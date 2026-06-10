<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $sort = $request->string('sort', 'name')->toString();
        $direction = $request->string('direction', 'asc')->toString();

        return Inertia::render('Admin/Categories/Index', [

            'categories' => Category::query()
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->orderBy($sort, $direction)
                ->get(),

            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'category_type' => ['required', 'string'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category,
        ]);
    }

    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')
                    ->ignore($category->id),
            ],

            'description' => ['nullable', 'string'],
            'category_type' => ['required', 'string'],
            'sort_order' => ['integer'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}