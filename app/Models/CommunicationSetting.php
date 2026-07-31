<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationSetting extends Model
{
    protected $fillable = [
        'sender_name',
        'sender_email',
        'reply_to_name',
        'reply_to_email',
        'default_test_recipient',
        'updated_by_user_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
