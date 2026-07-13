<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetConfigurationValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_configuration_group_id', 'label', 'value', 'description', 'swatch_color',
        'image_path', 'is_default', 'is_active', 'sort_order', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function group(): BelongsTo { return $this->belongsTo(AssetConfigurationGroup::class, 'asset_configuration_group_id'); }
    public function rules(): HasMany { return $this->hasMany(AssetConfigurationRule::class)->orderBy('priority')->orderBy('id'); }
}
