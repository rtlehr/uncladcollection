<?php

namespace App\Models;

use App\Enums\AnalyticsEventName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'event_uuid', 'fingerprint', 'event_name', 'subject_type', 'subject_id', 'user_id',
        'session_id', 'source', 'channel', 'currency', 'value_cents',
        'dimensions', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_name' => AnalyticsEventName::class,
            'value_cents' => 'integer',
            'dimensions' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
