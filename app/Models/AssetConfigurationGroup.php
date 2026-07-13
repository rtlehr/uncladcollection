<?php

namespace App\Models;

use App\Enums\AssetConfigurationDisplayType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetConfigurationGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_id', 'name', 'code', 'display_type', 'is_required', 'allows_multiple',
        'placeholder', 'help_text', 'minimum_value', 'maximum_value', 'step_value',
        'sort_order', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'display_type' => AssetConfigurationDisplayType::class,
            'is_required' => 'boolean',
            'allows_multiple' => 'boolean',
            'minimum_value' => 'decimal:4',
            'maximum_value' => 'decimal:4',
            'step_value' => 'decimal:4',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function values(): HasMany { return $this->hasMany(AssetConfigurationValue::class)->orderBy('sort_order')->orderBy('id'); }
    public function activeValues(): HasMany { return $this->values()->where('is_active', true); }
}
