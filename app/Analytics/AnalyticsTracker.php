<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AnalyticsTracker
{
    public function record(
        AnalyticsEventName $event,
        ?Model $subject = null,
        ?User $user = null,
        array $dimensions = [],
        ?int $valueCents = null,
        ?string $currency = null,
        ?string $source = null,
        ?string $channel = null,
    ): AnalyticsEvent {
        return AnalyticsEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_name' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'user_id' => $user?->getKey(),
            'source' => $source,
            'channel' => $channel,
            'currency' => $currency,
            'value_cents' => $valueCents,
            'dimensions' => $dimensions ?: null,
            'occurred_at' => now(),
        ]);
    }
}
