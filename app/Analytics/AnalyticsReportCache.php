<?php

namespace App\Analytics;

use Closure;
use Illuminate\Support\Facades\Cache;

final class AnalyticsReportCache
{
    public function remember(string $report, AnalyticsPeriod $period, array $filters, Closure $callback): mixed
    {
        $seconds = max(0, (int) config('analytics.report_cache_seconds', 300));

        if ($seconds === 0) {
            return $callback();
        }

        return Cache::remember($this->key($report, $period, $filters), now()->addSeconds($seconds), $callback);
    }

    public function key(string $report, AnalyticsPeriod $period, array $filters = []): string
    {
        ksort($filters);

        return 'analytics:v'.Cache::get('analytics:version', 1).':'.$report.':'.hash('sha256', json_encode([
            'start' => $period->start->toIso8601String(),
            'end' => $period->end->toIso8601String(),
            'filters' => $filters,
        ], JSON_THROW_ON_ERROR));
    }

    public function flush(): void
    {
        $version = (int) Cache::get('analytics:version', 1);
        Cache::forever('analytics:version', $version + 1);
    }
}
