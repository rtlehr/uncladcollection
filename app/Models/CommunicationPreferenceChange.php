<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationPreferenceChange extends Model
{
    protected $fillable = ['user_id', 'category', 'channel', 'old_value', 'new_value', 'source', 'ip_address', 'user_agent', 'changed_at'];

    protected $casts = ['old_value' => 'boolean', 'new_value' => 'boolean', 'changed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
