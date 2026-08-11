<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PublicPage extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const TYPE_STANDARD = 'standard';
    public const TYPE_CONTACT = 'contact';
    public const TYPE_FAQ = 'faq';
    public const TYPE_LEGAL = 'legal';
    public const TYPE_SUPPORT = 'support';
    public const NAV_HEADER = 'header';
    public const NAV_FOOTER_COMPANY = 'footer_company';
    public const NAV_FOOTER_RESOURCES = 'footer_resources';
    public const NAV_FOOTER_LEGAL = 'footer_legal';

    protected $fillable = [
        'created_by_user_id', 'updated_by_user_id', 'parent_id', 'title', 'slug', 'eyebrow',
        'introduction', 'content', 'header_image_original_path', 'header_image_path',
        'header_image_edit', 'header_image_alt', 'page_type', 'status', 'is_active',
        'published_at', 'navigation_label', 'navigation_locations', 'sort_order',
        'seo_title', 'seo_description', 'canonical_url', 'legal_version',
        'effective_date', 'revised_date',
    ];

    protected $appends = ['header_image_url', 'header_image_original_url'];

    protected $casts = [
        'is_active' => 'boolean', 'published_at' => 'datetime',
        'navigation_locations' => 'array', 'header_image_edit' => 'array',
        'sort_order' => 'integer', 'effective_date' => 'date', 'revised_date' => 'date',
    ];

    public function getRouteKeyName(): string { return 'slug'; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by_user_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title'); }
    public function activities(): MorphMany { return $this->morphMany(AdminActivity::class, 'subject'); }
    public function faqItems(): HasMany { return $this->hasMany(PublicPageFaqItem::class)->orderBy('sort_order')->orderBy('id'); }
    public function activeFaqItems(): HasMany { return $this->faqItems()->where('is_active', true); }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('is_active', true)
            ->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->is_active && $this->published_at?->lte(now());
    }

    public function getHeaderImageUrlAttribute(): ?string
    {
        return $this->header_image_path ? Storage::disk('public')->url($this->header_image_path) : null;
    }

    public function getHeaderImageOriginalUrlAttribute(): ?string
    {
        return $this->header_image_original_path ? Storage::disk('public')->url($this->header_image_original_path) : null;
    }

    public function getActivityName(): string { return $this->title; }
    public function navigationLabel(): string { return $this->navigation_label ?: $this->title; }

    public function publicUrl(): string
    {
        return $this->page_type === self::TYPE_SUPPORT
            ? route('support.landing', absolute: false)
            : '/'.$this->slug;
    }
}
