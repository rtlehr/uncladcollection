<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecentlyViewedAsset extends Model
{
    protected $fillable = [
        'user_id',
        'asset_id',
        'source',
        'view_count',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'view_count' => 'integer',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
