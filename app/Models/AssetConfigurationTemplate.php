<?php

namespace App\Models;

use App\Enums\AssetConfigurationDisplayType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetConfigurationTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'display_type', 'is_required_default',
        'allows_multiple_default', 'placeholder', 'help_text', 'minimum_value',
        'maximum_value', 'step_value', 'sort_order', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'display_type' => AssetConfigurationDisplayType::class,
            'is_required_default' => 'boolean',
            'allows_multiple_default' => 'boolean',
            'minimum_value' => 'decimal:4',
            'maximum_value' => 'decimal:4',
            'step_value' => 'decimal:4',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AssetConfigurationTemplateValue::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeValues(): HasMany
    {
        return $this->values()->where('is_active', true);
    }
}
