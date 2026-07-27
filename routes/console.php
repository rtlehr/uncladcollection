<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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


Artisan::command('support:prune {--dry-run} {--attachments-only}', function (): int {
    $attachmentCutoff = now()->subDays(max(1, (int) config('support.attachments.retention_days', 365)));
    $ticketCutoff = now()->subDays(max(1, (int) config('support.retention.closed_ticket_days', 2555)));
    $chunk = max(50, (int) config('support.retention.prune_chunk_size', 500));

    $attachments = \App\Models\SupportTicketAttachment::query()
        ->whereNull('redacted_at')
        ->whereHas('ticket', fn ($q) => $q->whereNotNull('closed_at')->where('closed_at', '<', $attachmentCutoff));
    $attachmentCount = (clone $attachments)->count();

    $tickets = \App\Models\SupportTicket::onlyTrashed()
        ->where('deleted_at', '<', $ticketCutoff);
    $ticketCount = (clone $tickets)->count();

    if ($this->option('dry-run')) {
        $this->info("{$attachmentCount} closed-ticket attachments would be removed.");
        if (! $this->option('attachments-only')) {
            $this->info("{$ticketCount} soft-deleted tickets would be permanently deleted.");
        }
        return 0;
    }

    $removed = 0;
    $attachments->orderBy('id')->chunkById($chunk, function ($rows) use (&$removed): void {
        foreach ($rows as $attachment) {
            \Illuminate\Support\Facades\Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->update([
                'redacted_at' => now(),
                'redaction_reason' => 'Removed by configured retention policy.',
                'path' => '',
                'is_customer_visible' => false,
            ]);
            $removed++;
        }
    });

    $deleted = 0;
    if (! $this->option('attachments-only')) {
        $tickets->orderBy('id')->chunkById($chunk, function ($rows) use (&$deleted): void {
            foreach ($rows as $ticket) {
                $ticket->forceDelete();
                $deleted++;
            }
        });
    }

    $this->info("Removed {$removed} retained attachments and permanently deleted {$deleted} tickets.");
    return 0;
})->purpose('Apply configured support attachment and ticket retention policies');

Artisan::command('support:validate', function (): int {
    $errors = [];
    foreach ([
        'admin.support.dashboard',
        'admin.support.reports',
        'admin.support.tickets.index',
        'support.store',
        'support.guest.reply',
    ] as $routeName) {
        if (! \Illuminate\Support\Facades\Route::has($routeName)) {
            $errors[] = "Missing route: {$routeName}";
        }
    }

    foreach (['view_support_tickets', 'view_support_reports', 'manage_support_tickets'] as $permission) {
        if (! \App\Models\Permission::query()->where('name', $permission)->exists()) {
            $errors[] = "Missing permission: {$permission}";
        }
    }

    foreach (['support_tickets', 'support_ticket_messages', 'support_ticket_attachments'] as $table) {
        if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
            $errors[] = "Missing table: {$table}";
        }
    }

    foreach ($errors as $error) {
        $this->error($error);
    }

    if ($errors !== []) {
        $this->error('Support validation failed.');
        return 1;
    }

    $this->info('Support validation passed.');
    return 0;
})->purpose('Validate support routes, permissions, and schema');


Artisan::command('page-help:export {--path= : Output path relative to the project root}', function (): int {
    $path = (string) ($this->option('path') ?: 'database/seeders/data/page-help.json');
    $absolutePath = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
        ? $path
        : base_path($path);

    \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($absolutePath));
    \Illuminate\Support\Facades\File::put(
        $absolutePath,
        app(\App\Services\PageHelp\PageHelpTransferService::class)->exportJson(),
    );

    $this->info('Exported Page Help content to '.$absolutePath);
    return 0;
})->purpose('Export database Page Help content to the version-controlled seed JSON file');

Artisan::command('page-help:import {path? : JSON path relative to the project root} {--replace} {--dry-run}', function (): int {
    $path = (string) ($this->argument('path') ?: 'database/seeders/data/page-help.json');
    $absolutePath = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
        ? $path
        : base_path($path);

    if (! \Illuminate\Support\Facades\File::exists($absolutePath)) {
        $this->error('Page Help export not found: '.$absolutePath);
        return 1;
    }

    try {
        $summary = app(\App\Services\PageHelp\PageHelpTransferService::class)->importJson(
            \Illuminate\Support\Facades\File::get($absolutePath),
            $this->option('replace') ? 'replace' : 'merge',
            dryRun: (bool) $this->option('dry-run'),
        );
    } catch (\InvalidArgumentException $exception) {
        $this->error($exception->getMessage());
        return 1;
    }

    $this->table(['Created', 'Updated', 'Unchanged', 'Deleted'], [[
        $summary['created'], $summary['updated'], $summary['unchanged'], $summary['deleted'],
    ]]);

    foreach ($summary['missing_roles'] as $role) {
        $this->warn('Missing role: '.$role);
    }
    foreach ($summary['missing_permissions'] as $permission) {
        $this->warn('Missing permission: '.$permission);
    }

    if ($summary['dry_run']) {
        $this->comment('Dry run only; no Page Help records were changed.');
    }

    return 0;
})->purpose('Import a Page Help JSON export in merge or replace mode');


Schedule::command('assets:rebuild-trending')->hourly()->withoutOverlapping();

Schedule::command('discovery:rebuild-user-affinities')->dailyAt('02:30')->withoutOverlapping();
