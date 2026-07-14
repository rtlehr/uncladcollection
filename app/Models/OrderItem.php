<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'order_id', 'image_id', 'asset_id', 'license_type_id', 'asset_offering_id',
        'status', 'fulfillment_type', 'commerce_version', 'quantity',
        'unit_price_cents', 'total_price_cents', 'image_title', 'asset_title',
        'license_name', 'offering_name', 'license_terms', 'configuration_hash',
        'configuration_snapshot', 'shipping_address_snapshot', 'pricing_snapshot', 'included_asset_files_snapshot',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'total_price_cents' => 'integer',
        'configuration_snapshot' => 'array',
        'shipping_address_snapshot' => 'array',
        'pricing_snapshot' => 'array',
        'included_asset_files_snapshot' => 'array',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function image(): BelongsTo { return $this->belongsTo(Image::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function licenseType(): BelongsTo { return $this->belongsTo(LicenseType::class); }
    public function assetOffering(): BelongsTo { return $this->belongsTo(AssetOffering::class); }
    public function license(): HasOne { return $this->hasOne(License::class); }
    public function downloads(): HasMany { return $this->hasMany(Download::class); }
    public function getUnitPriceFormattedAttribute(): string { return '$'.number_format($this->unit_price_cents / 100, 2); }
    public function getTotalPriceFormattedAttribute(): string { return '$'.number_format($this->total_price_cents / 100, 2); }
}
