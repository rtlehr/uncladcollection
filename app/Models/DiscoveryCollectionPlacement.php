<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveryCollectionPlacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'placement',
        'content_type',
        'audience',
        'eyebrow',
        'heading',
        'description',
        'call_to_action',
        'sort_order',
        'starts_at',
        'ends_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForAudience(Builder $query, bool $authenticated): Builder
    {
        return $query->whereIn('audience', $authenticated ? ['all', 'authenticated'] : ['all', 'guest']);
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        if ($this->starts_at?->isFuture()) {
            return 'Upcoming';
        }

        if ($this->ends_at?->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }
}
