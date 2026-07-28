<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrivacyPreference extends Model
{
    protected $fillable = ['user_id', 'personalized_recommendations', 'retain_recently_viewed', 'allow_unlisted_wish_lists'];
    protected $casts = [
        'personalized_recommendations' => 'boolean',
        'retain_recently_viewed' => 'boolean',
        'allow_unlisted_wish_lists' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
