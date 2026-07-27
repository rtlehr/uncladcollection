<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_original_path',
        'cover_image_path',
        'cover_edit_data',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'cover_edit_data' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'cover_image_url',
        'cover_original_url',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function discoveryPlacements(): HasMany
    {
        return $this->hasMany(DiscoveryCollectionPlacement::class);
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_path
            ? Storage::disk('public')->url($this->cover_image_path)
            : null;
    }

    public function getCoverOriginalUrlAttribute(): ?string
    {
        return $this->cover_original_path
            ? Storage::disk('public')->url($this->cover_original_path)
            : null;
    }
}
