<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'image_unit_price_cents',
        'video_unit_price_cents',
        'minimum_price_cents',
        'currency',
        'download_limit',
        'expires_after_days',
        'max_resolution',
        'usage_terms',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'image_unit_price_cents' => 'integer',
        'video_unit_price_cents' => 'integer',
        'minimum_price_cents' => 'integer',
        'download_limit' => 'integer',
        'expires_after_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getPriceFormattedAttribute(): string
    {
        return '$' . number_format($this->price_cents / 100, 2);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function assetOfferings(): HasMany
    {
        return $this->hasMany(AssetOffering::class);
    }

}