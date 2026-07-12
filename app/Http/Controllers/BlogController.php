<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $categoryId = $request->integer('category_id') ?: null;
        $tagId = $request->integer('tag_id') ?: null;

        $featuredPosts = $this->blogCardQuery()
            ->published()
            ->where('is_featured', true)
            ->latest('published_at')
            ->limit(3)
            ->get();

        $posts = $this->blogCardQuery()
            ->published()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%")
                        ->orWhereHas(
                            'author',
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
                'category_id' => $categoryId ? (string) $categoryId : '',
                'tag_id' => $tagId ? (string) $tagId : '',
            ],
        ]);
    }

    public function show(BlogPost $blogPost): Response
    {
        abort_unless($blogPost->isPublished(), 404);

        $blogPost->increment('views_count');
        $blogPost->views_count++;

        $blogPost->load([
            'author:id,name,author_title,author_bio,author_website_url,avatar_path',
            'categories:id,name,slug',
            'tags:id,name,slug',
        ]);

        $categoryIds = $blogPost->categories->modelKeys();
        $tagIds = $blogPost->tags->modelKeys();

        $relatedPosts = $this->blogCardQuery()
            ->published()
            ->whereKeyNot($blogPost->id)
            ->where(function (Builder $query) use ($categoryIds, $tagIds) {
                if ($categoryIds !== []) {
                    $query->whereHas(
                        'categories',
                        fn (Builder $query) => $query->whereKey($categoryIds),
                    );
                } else {
                    $query->whereRaw('1 = 0');
                }

                if ($tagIds !== []) {
                    $query->orWhereHas(
                        'tags',
                        fn (Builder $query) => $query->whereKey($tagIds),
                    );
                }
            })
            ->orderByDesc('views_count')
            ->latest('published_at')
            ->limit(3)
            ->get();

        $authorPosts = $this->blogCardQuery(includeAuthor: false)
            ->published()
            ->whereKeyNot($blogPost->id)
            ->where('user_id', $blogPost->user_id)
            ->latest('published_at')
            ->limit(4)
            ->get();

        $previousPost = BlogPost::query()
            ->published()
            ->where('published_at', '<', $blogPost->published_at)
            ->latest('published_at')
            ->first([
                'id',
                'title',
                'slug',
            ]);

        $nextPost = BlogPost::query()
            ->published()
            ->where('published_at', '>', $blogPost->published_at)
            ->oldest('published_at')
            ->first([
                'id',
                'title',
                'slug',
            ]);

        $comments = collect();

        if ($blogPost->comments_visible) {
            $comments = $blogPost->approvedComments()
                ->root()
                ->select([
                    'id',
                    'commentable_type',
                    'commentable_id',
                    'user_id',
                    'parent_id',
                    'body',
                    'status',
                    'depth',
                    'likes_count',
                    'reports_count',
                    'is_pinned',
                    'is_edited',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'user:id,name,username,avatar_path',
                    'approvedReplies.user:id,name,username,avatar_path',
                    'approvedReplies.approvedReplies.user:id,name,username,avatar_path',
                ])
                ->pinnedFirst()
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        return Inertia::render('Blog/Show', [
            'blogPost' => $blogPost,
            'relatedPosts' => $relatedPosts,
            'authorPosts' => $authorPosts,
            'previousPost' => $previousPost,
            'nextPost' => $nextPost,
            'comments' => $comments,
        ]);
    }

    private function blogCardQuery(bool $includeAuthor = true): Builder
    {
        $query = BlogPost::query()
            ->select([
                'id',
                'user_id',
                'title',
                'slug',
                'excerpt',
                'content',
                'featured_image_path',
                'header_image_path',
                'icon_image_path',
                'status',
                'published_at',
                'expires_at',
                'is_featured',
                'is_active',
                'views_count',
                'created_at',
            ])
            ->with([
                'categories:id,name,slug',
                'tags:id,name,slug',
            ]);

        if ($includeAuthor) {
            $query->with('author:id,name');
        }

        return $query;
    }
}
