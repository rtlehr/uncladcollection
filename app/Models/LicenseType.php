<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicenseType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
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
        'download_limit' => 'integer',
        'expires_after_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getPriceFormattedAttribute(): string
    {
        return '$' . number_format($this->price_cents / 100, 2);
    }
}