<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetOffering extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'asset_id', 'license_type_id', 'name', 'description', 'image_units', 'video_units',
        'price_cents', 'price_adjustment_cents', 'price_override_cents', 'currency',
        'download_limit', 'expires_after_days', 'include_all_active_files', 'is_active',
        'sort_order', 'metadata',
    ];

    protected $casts = [
        'image_units' => 'integer',
        'video_units' => 'integer',
        'price_cents' => 'integer',
        'price_adjustment_cents' => 'integer',
        'price_override_cents' => 'integer',
        'download_limit' => 'integer',
        'expires_after_days' => 'integer',
        'include_all_active_files' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function licenseType(): BelongsTo { return $this->belongsTo(LicenseType::class); }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(AssetFile::class, 'asset_offering_files')
            ->withPivot('sort_order')->withTimestamps()->orderByPivot('sort_order');
    }

    public function includedFiles()
    {
        if ($this->include_all_active_files) {
            return $this->asset->activeFiles()->where('is_downloadable', true)->orderBy('sort_order')->get();
        }

        return $this->files()->where('asset_files.is_active', true)->where('asset_files.is_downloadable', true)->get();
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(AssetPricingTier::class)->orderBy('minimum_quantity')->orderBy('sort_order');
    }

    public function getPriceFormattedAttribute(): string
    {
        return '$'.number_format($this->price_cents / 100, 2);
    }
}
