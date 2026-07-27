<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicPage extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const TYPE_STANDARD = 'standard';
    public const TYPE_CONTACT = 'contact';
    public const TYPE_FAQ = 'faq';
    public const TYPE_LEGAL = 'legal';

    public const NAV_HEADER = 'header';
    public const NAV_FOOTER_COMPANY = 'footer_company';
    public const NAV_FOOTER_RESOURCES = 'footer_resources';
    public const NAV_FOOTER_LEGAL = 'footer_legal';

    protected $fillable = [
        'created_by_user_id', 'updated_by_user_id', 'title', 'slug', 'eyebrow',
        'introduction', 'content', 'page_type', 'status', 'is_active',
        'published_at', 'navigation_label', 'navigation_locations', 'sort_order',
        'seo_title', 'seo_description', 'canonical_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'navigation_locations' => 'array',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(AdminActivity::class, 'subject');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->is_active
            && $this->published_at?->lte(now());
    }

    public function getActivityName(): string
    {
        return $this->title;
    }

    public function navigationLabel(): string
    {
        return $this->navigation_label ?: $this->title;
    }
};
