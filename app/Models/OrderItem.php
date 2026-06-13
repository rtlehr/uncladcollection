<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'order_id',
        'image_id',
        'license_type_id',
        'status',
        'quantity',
        'unit_price_cents',
        'total_price_cents',
        'image_title',
        'license_name',
        'license_terms',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'total_price_cents' => 'integer',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function licenseType(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class);
    }

    public function getUnitPriceFormattedAttribute(): string
    {
        return '$' . number_format($this->unit_price_cents / 100, 2);
    }

    public function getTotalPriceFormattedAttribute(): string
    {
        return '$' . number_format($this->total_price_cents / 100, 2);
    }

    public function license()
    {
        return $this->hasOne(License::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

}