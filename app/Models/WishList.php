<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WishList extends Model
{
    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'slug',
        'description',
        'visibility',
        'is_default',
        'share_token',
        'notify_price_changes',
        'notify_availability_changes',
        'notify_collection_changes',
        'sort_order',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'notify_price_changes' => 'boolean',
            'notify_availability_changes' => 'boolean',
            'notify_collection_changes' => 'boolean',
            'sort_order' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WishList $wishList): void {
            $wishList->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishListItem::class)->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isShareable(): bool
    {
        return $this->visibility === 'unlisted' && filled($this->share_token);
    }
}
