<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Tag;
use App\Services\AdminActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminBlogPostController extends Controller
{
    public function __construct(
        protected AdminActivityService $adminActivityService
    ) {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $blogPosts = BlogPost::query()
            ->with(['author:id,name,email', 'categories:id,name', 'tags:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/BlogPosts/Index', [
            'blogPosts' => $blogPosts,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => [
                BlogPost::STATUS_DRAFT,
                BlogPost::STATUS_PUBLISHED,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/BlogPosts/Create', [
            'categories' => Category::query()
                ->where('category_type', 'blog')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'tags' => Tag::query()
                ->where('tag_type', 'blog')
                ->orderBy('name')
                ->get(['id', 'name']),

            'statuses' => [
                BlogPost::STATUS_DRAFT,
                BlogPost::STATUS_PUBLISHED,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_posts,slug'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],

            'featured_image' => ['nullable', 'image', 'max:5120'],
            'header_image' => ['nullable', 'image', 'max:5120'],
            'icon_image' => ['nullable', 'image', 'max:2048'],

            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],

            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],

            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $featuredImagePath = $request->hasFile('featured_image')
            ? $request->file('featured_image')->store('blog/featured-images', 'public')
            : null;

        $headerImagePath = $request->hasFile('header_image')
            ? $request->file('header_image')->store('blog/header-images', 'public')
            : null;

        $iconImagePath = $request->hasFile('icon_image')
            ? $request->file('icon_image')->store('blog/icon-images', 'public')
            : null;

        $blogPost = BlogPost::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,

            'featured_image_path' => $featuredImagePath,
            'header_image_path' => $headerImagePath,
            'icon_image_path' => $iconImagePath,

            'status' => $validated['status'],
            'published_at' => $validated['status'] === BlogPost::STATUS_PUBLISHED
                ? ($validated['published_at'] ?? now())
                : ($validated['published_at'] ?? null),

            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,

            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $blogPost->categories()->sync($validated['category_ids'] ?? []);
        $blogPost->tags()->sync($validated['tag_ids'] ?? []);

        $this->adminActivityService->created(
            subject: $blogPost,
            description: 'Created blog post.'
        );

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('success', 'Blog post created successfully.');
    }

    public function show(BlogPost $blogPost): Response
    {
        $blogPost->load([
            'author:id,name,email',
            'categories:id,name',
            'tags:id,name',
        ]);

        $activity = $blogPost->activities()
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('Admin/BlogPosts/Show', [
            'blogPost' => $blogPost,
            'activity' => $activity,
        ]);
    }

    public function edit(BlogPost $blogPost): Response
    {
        $blogPost->load(['categories:id,name', 'tags:id,name']);

        return Inertia::render('Admin/BlogPosts/Edit', [
            'blogPost' => [
                ...$blogPost->toArray(),
                'category_ids' => $blogPost->categories->pluck('id')->values(),
                'tag_ids' => $blogPost->tags->pluck('id')->values(),
            ],

            'categories' => Category::query()
                ->where('category_type', 'blog')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),

            'tags' => Tag::query()
                ->where('tag_type', 'blog')
                ->orderBy('name')
                ->get(['id', 'name']),

            'statuses' => [
                BlogPost::STATUS_DRAFT,
                BlogPost::STATUS_PUBLISHED,
            ],
        ]);
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:blog_posts,slug,' . $blogPost->id],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],

            'featured_image' => ['nullable', 'image', 'max:5120'],
            'header_image' => ['nullable', 'image', 'max:5120'],
            'icon_image' => ['nullable', 'image', 'max:2048'],

            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],

            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],

            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $oldValues = $blogPost->getAttributes();
        $oldCategoryIds = $blogPost->categories()->pluck('categories.id')->values()->all();
        $oldTagIds = $blogPost->tags()->pluck('tags.id')->values()->all();

        $featuredImagePath = $blogPost->featured_image_path;
        $headerImagePath = $blogPost->header_image_path;
        $iconImagePath = $blogPost->icon_image_path;

        if ($request->hasFile('featured_image')) {
            if ($featuredImagePath) {
                Storage::disk('public')->delete($featuredImagePath);
            }

            $featuredImagePath = $request->file('featured_image')
                ->store('blog/featured-images', 'public');
        }

        if ($request->hasFile('header_image')) {
            if ($headerImagePath) {
                Storage::disk('public')->delete($headerImagePath);
            }

            $headerImagePath = $request->file('header_image')
                ->store('blog/header-images', 'public');
        }

        if ($request->hasFile('icon_image')) {
            if ($iconImagePath) {
                Storage::disk('public')->delete($iconImagePath);
            }

            $iconImagePath = $request->file('icon_image')
                ->store('blog/icon-images', 'public');
        }

        $blogPost->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,

            'featured_image_path' => $featuredImagePath,
            'header_image_path' => $headerImagePath,
            'icon_image_path' => $iconImagePath,

            'status' => $validated['status'],
            'published_at' => $validated['status'] === BlogPost::STATUS_PUBLISHED
                ? ($validated['published_at'] ?? $blogPost->published_at ?? now())
                : ($validated['published_at'] ?? null),

            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,

            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $blogPost->categories()->sync($validated['category_ids'] ?? []);
        $blogPost->tags()->sync($validated['tag_ids'] ?? []);

        $newCategoryIds = $blogPost->categories()->pluck('categories.id')->values()->all();
        $newTagIds = $blogPost->tags()->pluck('tags.id')->values()->all();

        $this->adminActivityService->logChanges(
            subject: $blogPost,
            oldValues: $oldValues,
            newValues: $blogPost->getAttributes(),
            action: 'updated'
        );

        if ($oldCategoryIds != $newCategoryIds) {
            $this->adminActivityService->log(
                action: 'updated',
                subject: $blogPost,
                fieldName: 'categories',
                oldValue: $oldCategoryIds,
                newValue: $newCategoryIds,
                description: 'Categories changed.'
            );
        }

        if ($oldTagIds != $newTagIds) {
            $this->adminActivityService->log(
                action: 'updated',
                subject: $blogPost,
                fieldName: 'tags',
                oldValue: $oldTagIds,
                newValue: $newTagIds,
                description: 'Tags changed.'
            );
        }

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('success', 'Blog post updated successfully.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $this->adminActivityService->deleted(
            subject: $blogPost,
            description: 'Deleted blog post.'
        );

        $blogPost->delete();

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('success', 'Blog post deleted successfully.');
    }
}