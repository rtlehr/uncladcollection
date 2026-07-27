<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAssetAffinity extends Model
{
    protected $fillable = ['user_id', 'dimension', 'value', 'score', 'signal_count', 'calculated_at'];

    protected function casts(): array
    {
        return ['score' => 'float', 'signal_count' => 'integer', 'calculated_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
