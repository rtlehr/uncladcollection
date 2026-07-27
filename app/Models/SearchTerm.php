<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SearchTerm extends Model
{
    protected $fillable = [
        'normalized_term', 'display_term', 'search_count', 'unique_searchers',
        'zero_result_count', 'average_results', 'click_count', 'favorite_count',
        'cart_count', 'order_count', 'revenue_cents', 'first_searched_at',
        'last_searched_at', 'is_content_opportunity',
    ];

    protected function casts(): array
    {
        return [
            'search_count' => 'integer', 'unique_searchers' => 'integer',
            'zero_result_count' => 'integer', 'average_results' => 'float',
            'click_count' => 'integer', 'favorite_count' => 'integer',
            'cart_count' => 'integer', 'order_count' => 'integer',
            'revenue_cents' => 'integer', 'is_content_opportunity' => 'boolean',
            'first_searched_at' => 'datetime', 'last_searched_at' => 'datetime',
        ];
    }

    public function variants(): HasMany { return $this->hasMany(SearchTermVariant::class); }
    public function mapping(): HasOne { return $this->hasOne(SearchTermMapping::class); }
}
