<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTrendingScore extends Model
{
    protected $fillable = [
        'asset_id', 'period', 'score', 'rank', 'components', 'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'rank' => 'integer',
            'components' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
