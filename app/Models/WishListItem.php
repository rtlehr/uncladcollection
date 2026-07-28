<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishListItem extends Model
{
    protected $fillable = [
        'wish_list_id',
        'asset_id',
        'note',
        'sort_order',
        'price_snapshot_cents',
        'availability_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'price_snapshot_cents' => 'integer',
        ];
    }

    public function wishList(): BelongsTo
    {
        return $this->belongsTo(WishList::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
