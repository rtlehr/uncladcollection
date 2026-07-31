<?php

namespace App\Services\Communications;

use App\Models\CommunicationPreferenceChange;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notifications\NotificationCategoryRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class CommunicationPreferenceService
{
    public function __construct(private readonly NotificationCategoryRegistry $categories) {}

    public function update(User $user, string $category, bool $inAppEnabled, bool $emailEnabled, string $source, ?Request $request = null): NotificationPreference
    {
        $definition = $this->categories->get($category);
        if (! array_key_exists($category, $this->categories->all())) {
            throw new InvalidArgumentException("Unknown notification category [{$category}].");
        }

        $preference = NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            ['in_app_enabled' => true, 'email_enabled' => $definition['default_email'], 'email_frequency' => 'immediate'],
        );

        $nextEmail = $definition['transactional'] ? true : $emailEnabled;
        $nextFrequency = $nextEmail ? 'immediate' : 'off';

        $this->record($user, $category, 'in_app', (bool) $preference->in_app_enabled, $inAppEnabled, $source, $request);
        $this->record($user, $category, 'email', (bool) $preference->email_enabled, $nextEmail, $source, $request);

        $preference->update([
            'in_app_enabled' => $inAppEnabled,
            'email_enabled' => $nextEmail,
            'email_frequency' => $nextFrequency,
        ]);

        return $preference->refresh();
    }

    public function unsubscribeUrl(User $user, string $category): ?string
    {
        $definition = $this->categories->get($category);
        if ($definition['transactional'] ?? false) return null;

        return URL::temporarySignedRoute('communications.unsubscribe.show', now()->addDays(30), [
            'user' => $user->getKey(),
            'category' => $category,
        ]);
    }

    private function record(User $user, string $category, string $channel, bool $old, bool $new, string $source, ?Request $request): void
    {
        if ($old === $new || ! Schema::hasTable('communication_preference_changes')) return;

        CommunicationPreferenceChange::query()->create([
            'user_id' => $user->id,
            'category' => $category,
            'channel' => $channel,
            'old_value' => $old,
            'new_value' => $new,
            'source' => $source,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'changed_at' => now(),
        ]);
    }
}
