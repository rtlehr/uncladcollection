<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Asset;
use App\Models\Tag;
use App\Services\AdminActivityService;
use App\Services\Ai\Support\AiKeywordExclusionFilter;
use App\Services\BlogImageService;
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
        protected AdminActivityService $adminActivityService,
        protected BlogImageService $blogImageService,
        protected AiKeywordExclusionFilter $keywordExclusionFilter,
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

            'header_image' => ['nullable', 'image', 'max:8192'],
            'header_image_original' => ['nullable', 'image', 'max:12288'],
            'header_image_edit_data' => ['nullable', 'string', 'max:20000'],
            'icon_image' => ['nullable', 'image', 'max:4096'],
            'icon_image_original' => ['nullable', 'image', 'max:8192'],
            'icon_image_edit_data' => ['nullable', 'string', 'max:20000'],

            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'ai_analysis_json' => ['nullable', 'string', 'max:100000'],
            'ai_analysis_settings_json' => ['nullable', 'string', 'max:20000'],

            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],

            'comments_enabled' => ['boolean'],
            'comments_visible' => ['boolean'],
            'comments_require_approval' => ['boolean'],

            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $headerImagePath = $request->hasFile('header_image')
            ? $this->blogImageService->storeRendered(
                $request->file('header_image'),
                'header-images',
            )
            : null;
        $headerOriginalPath = $request->hasFile('header_image_original')
            ? $this->blogImageService->storeOriginal(
                $request->file('header_image_original'),
                'header-images',
            )
            : null;
        $iconImagePath = $request->hasFile('icon_image')
            ? $this->blogImageService->storeRendered(
                $request->file('icon_image'),
                'icon-images',
            )
            : null;
        $iconOriginalPath = $request->hasFile('icon_image_original')
            ? $this->blogImageService->storeOriginal(
                $request->file('icon_image_original'),
                'icon-images',
            )
            : null;

        $imageEditData = [
            'header' => $this->decodeImageEditData(
                $validated['header_image_edit_data'] ?? null,
            ),
            'icon' => $this->decodeImageEditData(
                $validated['icon_image_edit_data'] ?? null,
            ),
        ];

        $blogPost = BlogPost::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?: Str::slug($validated['title']),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,

            'header_image_path' => $headerImagePath,
            'header_image_original_path' => $headerOriginalPath,
            'icon_image_path' => $iconImagePath,
            'icon_image_original_path' => $iconOriginalPath,
            'image_edit_data' => $imageEditData,

            'status' => $validated['status'],
            'published_at' => $validated['status'] === BlogPost::STATUS_PUBLISHED
                ? ($validated['published_at'] ?? now())
                : ($validated['published_at'] ?? null),
            'expires_at' => $validated['expires_at'] ?? null,

            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'ai_analysis' => $this->decodeJsonObject($validated['ai_analysis_json'] ?? null),
            'ai_analysis_settings' => $this->decodeJsonObject($validated['ai_analysis_settings_json'] ?? null),
            'ai_analyzed_at' => ! empty($validated['ai_analysis_json']) ? now() : null,

            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),

            'comments_enabled' => $request->boolean('comments_enabled', true),
            'comments_visible' => $request->boolean('comments_visible', true),
            'comments_require_approval' => $request->boolean('comments_require_approval'),
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

        $blogPostData = $blogPost->toArray();
        if (is_array($blogPostData['ai_analysis'] ?? null)) {
            $blogPostData['ai_analysis']['generated_tags'] = $this->keywordExclusionFilter->filter(
                is_array($blogPostData['ai_analysis']['generated_tags'] ?? null)
                    ? $blogPostData['ai_analysis']['generated_tags']
                    : [],
            );
        }

        return Inertia::render('Admin/BlogPosts/Edit', [
            'blogPost' => [
                ...$blogPostData,
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

            'header_image' => ['nullable', 'image', 'max:8192'],
            'header_image_original' => ['nullable', 'image', 'max:12288'],
            'header_image_edit_data' => ['nullable', 'string', 'max:20000'],
            'icon_image' => ['nullable', 'image', 'max:4096'],
            'icon_image_original' => ['nullable', 'image', 'max:8192'],
            'icon_image_edit_data' => ['nullable', 'string', 'max:20000'],

            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'ai_analysis_json' => ['nullable', 'string', 'max:100000'],
            'ai_analysis_settings_json' => ['nullable', 'string', 'max:20000'],

            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],

            'comments_enabled' => ['boolean'],
            'comments_visible' => ['boolean'],
            'comments_require_approval' => ['boolean'],

            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $oldValues = $blogPost->getAttributes();
        $oldCategoryIds = $blogPost->categories()->pluck('categories.id')->values()->all();
        $oldTagIds = $blogPost->tags()->pluck('tags.id')->values()->all();

        $headerImagePath = $blogPost->header_image_path;
        $headerOriginalPath = $blogPost->header_image_original_path;
        $iconImagePath = $blogPost->icon_image_path;
        $iconOriginalPath = $blogPost->icon_image_original_path;
        $imageEditData = $blogPost->image_edit_data ?? [];


        if ($request->hasFile('header_image')) {
            $newPath = $this->blogImageService->storeRendered(
                $request->file('header_image'),
                'header-images',
            );
            $this->blogImageService->delete($headerImagePath);
            $headerImagePath = $newPath;
            $imageEditData['header'] = $this->decodeImageEditData(
                $validated['header_image_edit_data'] ?? null,
            );
        }

        if ($request->hasFile('header_image_original')) {
            $newPath = $this->blogImageService->storeOriginal(
                $request->file('header_image_original'),
                'header-images',
            );
            $this->blogImageService->delete($headerOriginalPath);
            $headerOriginalPath = $newPath;
        }

        if ($request->hasFile('icon_image')) {
            $newPath = $this->blogImageService->storeRendered(
                $request->file('icon_image'),
                'icon-images',
            );
            $this->blogImageService->delete($iconImagePath);
            $iconImagePath = $newPath;
            $imageEditData['icon'] = $this->decodeImageEditData(
                $validated['icon_image_edit_data'] ?? null,
            );
        }

        if ($request->hasFile('icon_image_original')) {
            $newPath = $this->blogImageService->storeOriginal(
                $request->file('icon_image_original'),
                'icon-images',
            );
            $this->blogImageService->delete($iconOriginalPath);
            $iconOriginalPath = $newPath;
        }


        $blogPost->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'] ?? null,

            'header_image_path' => $headerImagePath,
            'header_image_original_path' => $headerOriginalPath,
            'icon_image_path' => $iconImagePath,
            'icon_image_original_path' => $iconOriginalPath,
            'image_edit_data' => $imageEditData,

            'status' => $validated['status'],
            'published_at' => $validated['status'] === BlogPost::STATUS_PUBLISHED
                ? ($validated['published_at'] ?? $blogPost->published_at ?? now())
                : ($validated['published_at'] ?? null),
            'expires_at' => $validated['expires_at'] ?? null,

            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'ai_analysis' => array_key_exists('ai_analysis_json', $validated)
                ? $this->decodeJsonObject($validated['ai_analysis_json'])
                : $blogPost->ai_analysis,
            'ai_analysis_settings' => array_key_exists('ai_analysis_settings_json', $validated)
                ? $this->decodeJsonObject($validated['ai_analysis_settings_json'])
                : $blogPost->ai_analysis_settings,
            'ai_analyzed_at' => ! empty($validated['ai_analysis_json'])
                ? now()
                : $blogPost->ai_analyzed_at,

            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),

            'comments_enabled' => $request->boolean('comments_enabled', true),
            'comments_visible' => $request->boolean('comments_visible', true),
            'comments_require_approval' => $request->boolean('comments_require_approval'),
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

    public function uploadContentImage(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:8192'],
            'preset' => [
                'required',
                'in:blog-content-landscape,blog-content-portrait,blog-content-square',
            ],
            'edit_data' => ['nullable', 'string', 'max:20000'],
            'alt' => ['nullable', 'string', 'max:500'],
            'asset_id' => [
                'nullable',
                'integer',
                'exists:assets,id',
            ],
        ]);

        $path = $this->blogImageService->storeContentImage(
            $request->file('image'),
            $validated['preset'],
        );

        $asset = isset($validated['asset_id'])
            ? Asset::query()->find($validated['asset_id'])
            : null;

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'preset' => $validated['preset'],
            'alt' => $validated['alt'] ?? $asset?->title,
            'asset' => $asset
                ? [
                    'id' => $asset->id,
                    'title' => $asset->title,
                    'slug' => $asset->slug,
                    'photographer' => $asset->photographer,
                    'public_url' => route('assets.show', $asset),
                ]
                : null,
        ]);
    }

    public function imageLibrary(Request $request)
    {
        $search = $request->string('search')->toString();

        $assets = Asset::query()
            ->with([
                'primaryPreviewFile',
                'activeFiles',
            ])
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('photographer', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->limit(48)
            ->get()
            ->map(function (Asset $asset): ?array {
                $preview = $asset->primaryPreviewFile
                    ?? $asset->activeFiles->first(
                        fn ($file) => in_array(
                            $file->media_type->value,
                            ['image', 'vector'],
                            true,
                        ),
                    );

                if (! $preview) {
                    return null;
                }

                $previewUrl = route(
                    'admin.assets.files.preview',
                    [$asset, $preview],
                );

                return [
                    'id' => $asset->id,
                    'title' => $asset->title,
                    'slug' => $asset->slug,
                    'photographer' => $asset->photographer,
                    'thumbnail_url' => $previewUrl,
                    'icon_url' => $previewUrl,
                    'high_res_url' => $previewUrl,
                    'public_url' => route('assets.show', $asset),
                    'asset_type_label' => $asset->asset_type->label(),
                    'formats' => $asset->activeFiles
                        ->pluck('extension')
                        ->filter()
                        ->map(fn (string $extension) => strtoupper($extension))
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'images' => $assets,
        ]);
    }

    private function decodeImageEditData(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, mixed>|null */
    private function decodeJsonObject(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }


}