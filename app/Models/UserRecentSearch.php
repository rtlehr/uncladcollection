<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRecentSearch extends Model
{
    protected $fillable = ['user_id', 'term', 'normalized_term', 'searched_at'];

    protected function casts(): array
    {
        return ['searched_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
