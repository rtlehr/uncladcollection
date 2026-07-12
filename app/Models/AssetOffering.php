<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AssetOffering extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_id', 'license_type_id', 'name', 'description', 'price_cents', 'currency',
        'download_limit', 'expires_after_days', 'include_all_active_files', 'is_active',
        'sort_order', 'metadata',
    ];

    protected $casts = [
        'price_cents' => 'integer',
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

    public function getPriceFormattedAttribute(): string
    {
        return '$'.number_format($this->price_cents / 100, 2);
    }
}
