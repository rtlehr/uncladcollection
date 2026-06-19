<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $categoryId = $request->integer('category_id') ?: null;
        $tagId = $request->integer('tag_id') ?: null;

        $featuredPosts = BlogPost::query()
            ->published()
            ->with(['author:id,name', 'categories:id,name', 'tags:id,name'])
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(3)
            ->get();

        $posts = BlogPost::query()
            ->published()
            ->with(['author:id,name', 'categories:id,name', 'tags:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->whereHas('categories', function ($query) use ($categoryId) {
                    $query->where('categories.id', $categoryId);
                });
            })
            ->when($tagId, function ($query) use ($tagId) {
                $query->whereHas('tags', function ($query) use ($tagId) {
                    $query->where('tags.id', $tagId);
                });
            })
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'featuredPosts' => $featuredPosts,

            'categories' => Category::query()
                ->where('category_type', 'blog')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),

            'tags' => Tag::query()
                ->where('tag_type', 'blog')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),

            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'tag_id' => $tagId,
            ],
        ]);
    }

    public function show(BlogPost $blogPost): Response
    {
        abort_unless($blogPost->isPublished(), 404);

        $blogPost->increment('views_count');

        $blogPost->load([
            'author:id,name',
            'categories:id,name,slug',
            'tags:id,name,slug',
        ]);

        $relatedPosts = BlogPost::query()
            ->published()
            ->whereKeyNot($blogPost->id)
            ->with(['author:id,name', 'categories:id,name', 'tags:id,name'])
            ->where(function ($query) use ($blogPost) {
                $categoryIds = $blogPost->categories->pluck('id');

                if ($categoryIds->isNotEmpty()) {
                    $query->whereHas('categories', function ($query) use ($categoryIds) {
                        $query->whereIn('categories.id', $categoryIds);
                    });
                }
            })
            ->latest('published_at')
            ->take(3)
            ->get();

        return Inertia::render('Blog/Show', [
            'blogPost' => $blogPost,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}