<?php

namespace App\Services\Notifications;

use App\Models\Asset;
use App\Models\NotificationPreference;
use App\Models\NotificationWatchEvent;
use App\Models\User;
use App\Models\WishList;
use App\Notifications\CustomerAccountNotification;
use Illuminate\Support\Facades\Schema;

class CustomerNotificationService
{
    public function __construct(private readonly NotificationCategoryRegistry $categories) {}

    public function send(User $user, string $category, string $title, string $message, ?string $actionUrl = null, ?string $actionLabel = null, array $context = []): void
    {
        if (! Schema::hasTable('notifications')) return;

        $definition = $this->categories->get($category);
        $preference = Schema::hasTable('notification_preferences')
            ? NotificationPreference::query()->firstOrCreate(
                ['user_id' => $user->id, 'category' => $category],
                ['in_app_enabled' => true, 'email_enabled' => $definition['default_email'], 'email_frequency' => 'immediate']
            )
            : null;

        $channels = [];
        if (! $preference || $preference->in_app_enabled) $channels[] = 'database';
        if (($definition['transactional'] || $preference?->email_enabled) && ($preference?->email_frequency ?? 'immediate') === 'immediate') $channels[] = 'mail';
        if ($channels === []) return;

        $user->notify(new CustomerAccountNotification($category, $title, $message, $actionUrl, $actionLabel, $context, array_values(array_unique($channels))));
    }

    public function sendOnce(
        User $user,
        string $eventType,
        string $fingerprint,
        string $category,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        array $context = [],
        ?Asset $asset = null,
        ?WishList $wishList = null,
    ): bool {
        if (! Schema::hasTable('notification_watch_events')) return false;

        $created = NotificationWatchEvent::query()->firstOrCreate(
            ['user_id' => $user->id, 'event_type' => $eventType, 'fingerprint' => $fingerprint],
            ['asset_id' => $asset?->id, 'wish_list_id' => $wishList?->id, 'context' => $context, 'notified_at' => now()],
        );

        if (! $created->wasRecentlyCreated) return false;

        $this->send($user, $category, $title, $message, $actionUrl, $actionLabel, $context);
        return true;
    }
}
