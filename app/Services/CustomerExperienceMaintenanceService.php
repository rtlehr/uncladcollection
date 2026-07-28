<?php

namespace App\Services;

use App\Models\License;
use App\Models\NotificationWatchEvent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CustomerExperienceMaintenanceService
{
    public function health(): array
    {
        return [
            'active_but_expired_licenses' => License::query()->where('status', License::STATUS_ACTIVE)->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'notification_watch_events' => NotificationWatchEvent::query()->count(),
            'old_notification_watch_events' => NotificationWatchEvent::query()->where('created_at', '<', now()->subDays((int) config('customer-notifications.retention_days', 365)))->count(),
            'temporary_download_packages' => count($this->packageFiles()),
            'stale_download_packages' => count($this->packageFiles(now()->subDay()->getTimestamp())),
            'notifications_table_ready' => Schema::hasTable('notifications'),
        ];
    }

    public function maintain(bool $dryRun = false): array
    {
        $cutoff = now()->subDays((int) config('customer-notifications.retention_days', 365));
        $watchQuery = NotificationWatchEvent::query()->where('created_at', '<', $cutoff);
        $watchCount = (clone $watchQuery)->count();
        $packages = $this->packageFiles(now()->subDay()->getTimestamp());

        if (! $dryRun) {
            $watchQuery->delete();
            foreach ($packages as $path) File::delete($path);
        }

        return [
            'watch_events' => $watchCount,
            'download_packages' => count($packages),
            'dry_run' => $dryRun,
        ];
    }

    private function packageFiles(?int $olderThan = null): array
    {
        $directory = storage_path('app/private/download-packages');
        if (! File::isDirectory($directory)) return [];

        return collect(File::files($directory))
            ->filter(fn ($file): bool => $file->getExtension() === 'zip')
            ->filter(fn ($file): bool => $olderThan === null || $file->getMTime() < $olderThan)
            ->map(fn ($file): string => $file->getPathname())
            ->values()->all();
    }
}
