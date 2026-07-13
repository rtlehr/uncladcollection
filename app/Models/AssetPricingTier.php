<?php

namespace App\Models;

use App\Enums\AssetPricingTierType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetPricingTier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_id',
        'asset_offering_id',
        'minimum_quantity',
        'maximum_quantity',
        'pricing_type',
        'unit_price_cents',
        'percentage_off',
        'currency',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'minimum_quantity' => 'integer',
            'maximum_quantity' => 'integer',
            'pricing_type' => AssetPricingTierType::class,
            'unit_price_cents' => 'integer',
            'percentage_off' => 'decimal:4',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(AssetOffering::class, 'asset_offering_id');
    }

    public function appliesToQuantity(int $quantity): bool
    {
        return $quantity >= $this->minimum_quantity
            && ($this->maximum_quantity === null || $quantity <= $this->maximum_quantity);
    }
}
