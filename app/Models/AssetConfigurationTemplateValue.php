<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetConfigurationTemplateValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_configuration_template_id', 'label', 'value', 'description',
        'swatch_color', 'image_path', 'price_adjustment_cents', 'currency',
        'is_default', 'is_active', 'sort_order', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment_cents' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssetConfigurationTemplate::class, 'asset_configuration_template_id');
    }
}
