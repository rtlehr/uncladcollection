<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailDeliveryLog extends Model
{
    protected $fillable = [
        'retried_from_id', 'email_template_id', 'template_key', 'user_id', 'recipient_email',
        'subject', 'status', 'retry_count', 'message_id', 'failure_message', 'sent_at',
        'failed_at', 'context',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'context' => 'array',
    ];


    public function retriedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'retried_from_id');
    }

    public function retries(): HasMany
    {
        return $this->hasMany(self::class, 'retried_from_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
