<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetSearchDocument extends Model
{
    protected $primaryKey = 'asset_id';
    public $incrementing = false;

    protected $fillable = [
        'asset_id', 'normalized_title', 'search_text', 'orientation',
        'width', 'height', 'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'asset_id' => 'integer', 'width' => 'integer', 'height' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
