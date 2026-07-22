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

Artisan::command('analytics:validate {--strict : Treat missing analytics permissions as a failure}', function (): int {
    $requiredRoutes = [
        'admin.analytics.index',
        'admin.analytics.financial',
        'admin.analytics.financial.export',
        'admin.analytics.assets.index',
        'admin.analytics.assets.export',
        'admin.analytics.assets.show',
        'admin.analytics.customers.index',
        'admin.analytics.customers.export',
        'admin.analytics.customers.show',
        'admin.analytics.blog.index',
        'admin.analytics.blog.export',
        'admin.analytics.blog.show',
        'admin.analytics.campaigns.index',
        'admin.analytics.campaigns.export',
        'admin.analytics.campaigns.show',
        'admin.analytics.search.index',
        'admin.analytics.search.export',
        'admin.analytics.search.show',
        'admin.analytics.downloads.index',
        'admin.analytics.downloads.export',
        'admin.analytics.downloads.show',
        'admin.analytics.operations.index',
        'admin.analytics.operations.export',
        'admin.analytics.operations.show',
    ];

    $requiredColumns = [
        'analytics_events' => ['event_uuid', 'fingerprint', 'event_name', 'subject_type', 'subject_id', 'user_id', 'session_id', 'dimensions', 'occurred_at'],
        'orders' => ['status', 'fulfillment_status', 'paid_at', 'fulfilled_at'],
        'licenses' => ['status', 'download_limit', 'downloads_used', 'expires_at'],
        'downloads' => ['license_id', 'user_id', 'downloaded_at'],
    ];

    $errors = [];
    $warnings = [];

    foreach ($requiredRoutes as $routeName) {
        if (! Route::has($routeName)) {
            $errors[] = "Missing route: {$routeName}";
            continue;
        }

        $route = Route::getRoutes()->getByName($routeName);
        $middleware = $route?->gatherMiddleware() ?? [];

        if (! in_array('permission:view_reports', $middleware, true)) {
            $errors[] = "Route lacks view_reports permission: {$routeName}";
        }
    }

    foreach ($requiredColumns as $table => $columns) {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            $errors[] = "Missing table: {$table}";
            continue;
        }

        foreach ($columns as $column) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
                $errors[] = "Missing column: {$table}.{$column}";
            }
        }
    }

    foreach (['view_admin', 'view_reports'] as $permission) {
        if (! \App\Models\Permission::query()->where('name', $permission)->exists()) {
            $warnings[] = "Permission record not found: {$permission}";
        }
    }

    foreach ([
        'enabled', 'exclude_bots', 'deduplicate', 'deduplication_window_seconds',
        'retention_days', 'report_cache_seconds', 'report_row_limit',
    ] as $key) {
        if (config("analytics.{$key}") === null) {
            $errors[] = "Missing analytics configuration: {$key}";
        }
    }

    $this->newLine();
    $this->info('Epic 1 analytics validation');
    $this->line('Routes checked: '.count($requiredRoutes));
    $this->line('Schema groups checked: '.count($requiredColumns));

    foreach ($warnings as $warning) {
        $this->warn($warning);
    }

    foreach ($errors as $error) {
        $this->error($error);
    }

    if ($errors !== [] || ($this->option('strict') && $warnings !== [])) {
        $this->newLine();
        $this->error('Analytics validation failed.');
        return 1;
    }

    $this->newLine();
    $this->info('Analytics validation passed.');
    return 0;
})->purpose('Validate the Epic 1 analytics routes, permissions, schema, and configuration');
