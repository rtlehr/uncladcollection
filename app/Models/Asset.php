<?php

namespace App\Models;

use App\Enums\AssetFulfillmentType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Observers\AssetObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([AssetObserver::class])]
class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'legacy_image_id', 'collection_id', 'title', 'slug', 'description',
        'alt_text', 'seo_title', 'seo_description', 'keywords', 'dominant_colors', 'detected_objects',
        'asset_type', 'status', 'photographer', 'sort_order', 'is_active',
        'is_featured', 'trending_boost', 'suppress_from_trending', 'is_ai_generated', 'allows_quantity', 'fulfillment_type',
        'collects_shipping_address', 'shipping_address_required', 'downloads_count', 'favorites_count',
        'purchases_count', 'views_count', 'published_at', 'metadata',
        'presentation_images', 'primary_preview_file_id', 'poster_file_id',
    ];

    protected function casts(): array
    {
        return [
            'asset_type' => AssetType::class,
            'status' => AssetStatus::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'trending_boost' => 'integer',
            'suppress_from_trending' => 'boolean',
            'is_ai_generated' => 'boolean',
            'allows_quantity' => 'boolean',
            'fulfillment_type' => AssetFulfillmentType::class,
            'collects_shipping_address' => 'boolean',
            'shipping_address_required' => 'boolean',
            'downloads_count' => 'integer',
            'favorites_count' => 'integer',
            'purchases_count' => 'integer',
            'views_count' => 'integer',
            'published_at' => 'datetime',
            'metadata' => 'array',
            'keywords' => 'array',
            'dominant_colors' => 'array',
            'detected_objects' => 'array',
            'presentation_images' => 'array',
            'primary_preview_file_id' => 'integer',
            'poster_file_id' => 'integer',
        ];
    }

    public function legacyImage(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'legacy_image_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(AssetFile::class)->orderBy('sort_order')->orderBy('id');
    }

    public function fileRelationships(): HasMany
    {
        return $this->hasMany(AssetFileRelationship::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeFiles(): HasMany
    {
        return $this->files()->where('is_active', true);
    }

    public function primaryPreviewFile(): HasOne
    {
        return $this->hasOne(AssetFile::class, 'id', 'primary_preview_file_id');
    }

    public function posterFile(): HasOne
    {
        return $this->hasOne(AssetFile::class, 'id', 'poster_file_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', AssetStatus::Published)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function getActivityName(): string
    {
        return $this->title;
    }
    public function offerings(): HasMany
    {
        return $this->hasMany(AssetOffering::class)->orderBy('sort_order');
    }

    public function configurationGroups(): HasMany
    {
        return $this->hasMany(AssetConfigurationGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeConfigurationGroups(): HasMany
    {
        return $this->configurationGroups()->where('is_active', true);
    }


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'asset_category')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'asset_tag')->withTimestamps();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(AssetFavorite::class);
    }

    public function scopeDiscoverable(Builder $query): Builder
    {
        return app(\App\Services\AssetDiscoveryEligibilityService::class)->applyDiscoverable($query);
    }

    public function scopePurchasable(Builder $query): Builder
    {
        return app(\App\Services\AssetDiscoveryEligibilityService::class)->applyPurchasable($query);
    }


    public function trendingScores(): HasMany
    {
        return $this->hasMany(AssetTrendingScore::class);
    }

    public function recentViews(): HasMany
    {
        return $this->hasMany(RecentlyViewedAsset::class);
    }

    public function searchDocument(): HasOne
    {
        return $this->hasOne(AssetSearchDocument::class);
    }

    public function aiSuggestions(): HasMany
    {
        return $this->hasMany(AiAssetSuggestion::class)->latest();
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(AssetPricingTier::class)->orderBy('minimum_quantity')->orderBy('sort_order');
    }

}
