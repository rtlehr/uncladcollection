<?php

namespace App\Models;

use App\Enums\AssetConfigurationRuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetConfigurationRule extends Model
{
    protected $fillable = [
        'asset_configuration_value_id', 'asset_offering_id', 'rule_type', 'amount_cents',
        'percentage', 'currency', 'priority', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => AssetConfigurationRuleType::class,
            'amount_cents' => 'integer',
            'percentage' => 'decimal:4',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function value(): BelongsTo { return $this->belongsTo(AssetConfigurationValue::class, 'asset_configuration_value_id'); }
    public function offering(): BelongsTo { return $this->belongsTo(AssetOffering::class, 'asset_offering_id'); }
}
