<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetConfigurationSelection extends Model
{
    protected $fillable = [
        'uuid', 'asset_id', 'asset_offering_id', 'user_id', 'session_key', 'selections',
        'base_price_cents', 'price_adjustment_cents', 'total_price_cents', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'selections' => 'array',
            'base_price_cents' => 'integer',
            'price_adjustment_cents' => 'integer',
            'total_price_cents' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function offering(): BelongsTo { return $this->belongsTo(AssetOffering::class, 'asset_offering_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
