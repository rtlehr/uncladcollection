<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('analytics:prune {--days=} {--dry-run}', function (): int {
    $days = max(1, (int) ($this->option('days') ?: config('analytics.retention_days', 730)));
    $cutoff = now()->subDays($days);
    $query = \App\Models\AnalyticsEvent::query()->where('occurred_at', '<', $cutoff);
    $count = (clone $query)->count();

    if ($this->option('dry-run')) {
        $this->info("{$count} analytics events would be deleted before {$cutoff->toDateTimeString()}.");
        return 0;
    }

    $deleted = 0;
    $chunk = max(100, (int) config('analytics.prune_chunk_size', 5000));

    do {
        $ids = (clone $query)->orderBy('id')->limit($chunk)->pluck('id');
        $batch = $ids->isEmpty() ? 0 : \App\Models\AnalyticsEvent::query()->whereIn('id', $ids)->delete();
        $deleted += $batch;
    } while ($batch === $chunk);

    app(\App\Analytics\AnalyticsReportCache::class)->flush();
    $this->info("Deleted {$deleted} analytics events older than {$days} days.");

    return 0;
})->purpose('Prune analytics events beyond the configured retention period');
