<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        ?string $sessionId = null,
        ?string $deduplicationKey = null,
    ): AnalyticsEvent {
        if (! config('analytics.enabled', true) || $this->isExcludedBot()) {
            return new AnalyticsEvent([
                'event_uuid' => (string) Str::uuid(),
                'event_name' => $event,
                'occurred_at' => now(),
            ]);
        }

        $dimensions = AnalyticsDimensions::sanitize($dimensions);
        $sessionId ??= $this->sessionId();
        $fingerprint = $this->fingerprint($event, $subject, $user, $sessionId, $dimensions, $deduplicationKey);

        if (config('analytics.deduplicate', true)) {
            $existing = AnalyticsEvent::query()
                ->where('fingerprint', $fingerprint)
                ->where('occurred_at', '>=', now()->subSeconds(max(1, config('analytics.deduplication_window_seconds', 30))))
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return AnalyticsEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'fingerprint' => $fingerprint,
            'event_name' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'user_id' => $user?->getKey(),
            'session_id' => $sessionId,
            'source' => $source,
            'channel' => $channel,
            'currency' => $currency ? strtoupper(substr($currency, 0, 3)) : null,
            'value_cents' => $valueCents,
            'dimensions' => $dimensions ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function sessionId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app(Request::class);

        return $request->hasSession() ? mb_substr((string) $request->session()->getId(), 0, 255) : null;
    }

    private function isExcludedBot(): bool
    {
        if (! config('analytics.exclude_bots', true) || ! app()->bound('request')) {
            return false;
        }

        $agent = mb_strtolower((string) app(Request::class)->userAgent());

        return $agent !== '' && collect(config('analytics.bot_patterns', []))
            ->contains(fn (string $pattern): bool => str_contains($agent, mb_strtolower($pattern)));
    }

    private function fingerprint(
        AnalyticsEventName $event,
        ?Model $subject,
        ?User $user,
        ?string $sessionId,
        array $dimensions,
        ?string $deduplicationKey,
    ): string {
        return hash('sha256', json_encode([
            'event' => $event->value,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'actor' => $user?->getKey() ? 'user:'.$user->getKey() : 'session:'.($sessionId ?? 'anonymous'),
            'dimensions' => $dimensions,
            'key' => $deduplicationKey,
        ], JSON_THROW_ON_ERROR));
    }
}
