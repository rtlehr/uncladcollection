<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\Comment;

class BlogPost extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image_path',
        'header_image_path',
        'header_image_original_path',
        'icon_image_path',
        'icon_image_original_path',
        'image_edit_data',
        'status',
        'published_at',
        'seo_title',
        'seo_description',
        'ai_analysis',
        'ai_analysis_settings',
        'ai_analyzed_at',
        'is_featured',
        'is_active',
        'views_count',
        'expires_at',
        'comments_enabled',
        'comments_visible',
        'comments_require_approval',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'views_count' => 'integer',
        'expires_at' => 'datetime',
        'comments_enabled' => 'boolean',
        'comments_visible' => 'boolean',
        'comments_require_approval' => 'boolean',
        'image_edit_data' => 'array',
        'ai_analysis' => 'array',
        'ai_analysis_settings' => 'array',
        'ai_analyzed_at' => 'datetime',
    ];

    protected $appends = [
        'featured_image_url',
        'header_image_url',
        'header_image_original_url',
        'icon_image_url',
        'icon_image_original_url',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'blog_post_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'blog_post_tag');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(AdminActivity::class, 'subject');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image_path
            ? Storage::url($this->featured_image_path)
            : null;
    }

    public function getHeaderImageUrlAttribute(): ?string
    {
        return $this->header_image_path
            ? Storage::url($this->header_image_path)
            : null;
    }

    public function getHeaderImageOriginalUrlAttribute(): ?string
    {
        return $this->header_image_original_path
            ? Storage::url($this->header_image_original_path)
            : $this->header_image_url;
    }

    public function getIconImageOriginalUrlAttribute(): ?string
    {
        return $this->icon_image_original_path
            ? Storage::url($this->icon_image_original_path)
            : $this->icon_image_url;
    }

    public function getIconImageUrlAttribute(): ?string
    {
        return $this->icon_image_path
            ? Storage::url($this->icon_image_path)
            : null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->is_active
            && $this->published_at
            && $this->published_at->lte(now())
            && (
                ! $this->expires_at
                || $this->expires_at->gt(now())
            );
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function approvedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->where('status', Comment::STATUS_APPROVED);
    }

}