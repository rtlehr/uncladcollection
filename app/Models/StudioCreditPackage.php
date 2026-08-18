<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudioCreditPackage extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'credits', 'price_cents', 'currency', 'is_active', 'sort_order'];

    protected $casts = [
        'credits' => 'integer',
        'price_cents' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(StudioCreditTransaction::class);
    }

    public function getPriceFormattedAttribute(): string
    {
        return '$'.number_format($this->price_cents / 100, 2);
    }
}
