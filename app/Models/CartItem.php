<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'image_id',
        'asset_id',
        'license_type_id',
        'asset_offering_id',
        'quantity',
        'configuration_hash',
        'configuration_snapshot',
        'price_cents',
        'base_unit_price_cents',
        'configuration_adjustment_cents',
        'final_unit_price_cents',
        'line_total_cents',
        'pricing_snapshot',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'configuration_snapshot' => 'array',
            'price_cents' => 'integer',
            'base_unit_price_cents' => 'integer',
            'configuration_adjustment_cents' => 'integer',
            'final_unit_price_cents' => 'integer',
            'line_total_cents' => 'integer',
            'pricing_snapshot' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetOffering(): BelongsTo
    {
        return $this->belongsTo(AssetOffering::class);
    }

    public function licenseType(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class);
    }
}