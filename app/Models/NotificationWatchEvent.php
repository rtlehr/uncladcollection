<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationWatchEvent extends Model
{
    protected $fillable = ['user_id', 'asset_id', 'wish_list_id', 'event_type', 'fingerprint', 'context', 'notified_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'notified_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function wishList(): BelongsTo { return $this->belongsTo(WishList::class); }
}
