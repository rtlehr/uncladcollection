<?php

namespace App\Console\Commands;

use App\Models\NotificationWatchEvent;
use App\Services\Notifications\CustomerDiscoveryNotificationService;
use Illuminate\Console\Command;

class SendCustomerDiscoveryNotifications extends Command
{
    protected $signature = 'customer-notifications:send {--user=} {--wish-lists-only} {--interests-only} {--dry-run} {--prune}';
    protected $description = 'Send deduplicated wish-list and interest-based customer notifications';

    public function handle(CustomerDiscoveryNotificationService $service): int
    {
        if ($this->option('prune')) {
            $deleted = NotificationWatchEvent::query()->where('notified_at', '<', now()->subDays(config('customer-notifications.retention_days', 365)))->delete();
            $this->info("Pruned {$deleted} notification watch events.");
            return self::SUCCESS;
        }

        $userId = $this->option('user') ? (int) $this->option('user') : null;
        $dryRun = (bool) $this->option('dry-run');

        if (! $this->option('interests-only') && config('customer-notifications.wish_lists.enabled', true)) {
            $stats = $service->scanWishLists($userId, $dryRun);
            $this->line("Wish lists: {$stats['checked']} checked, {$stats['sent']} notifications, {$stats['updated']} snapshots updated.");
        }
        if (! $this->option('wish-lists-only') && config('customer-notifications.interests.enabled', true)) {
            $stats = $service->sendInterestMatches($userId, $dryRun);
            $this->line("Interests: {$stats['users']} users matched, {$stats['sent']} notifications.");
        }
        if ($dryRun) $this->warn('Dry run: no notifications or snapshot changes were saved.');
        return self::SUCCESS;
    }
}
