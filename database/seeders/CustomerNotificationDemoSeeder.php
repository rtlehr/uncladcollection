<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\CustomerAccountNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerNotificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->where('is_disabled', false)
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No enabled, verified users were found. No demo notifications were created.');

            return;
        }

        $this->removeExistingDemoNotifications($users);

        $templates = $this->templates();

        foreach ($users as $userIndex => $user) {
            $templateCount = count($templates);

            $offset = $templateCount > 0
                ? $userIndex % $templateCount
                : 0;

            $userTemplates = collect([
                ...array_slice($templates, $offset),
                ...array_slice($templates, 0, $offset),
            ])
                ->take(5)
                ->values();

            foreach ($userTemplates as $notificationIndex => $template) {
                $createdAt = now()->subHours(($userIndex * 7) + ($notificationIndex * 5) + 1);

                $user->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => CustomerAccountNotification::class,
                    'data' => [
                        'category' => $template['category'],
                        'title' => $template['title'],
                        'message' => $template['message'],
                        'action_url' => $template['action_url'],
                        'action_label' => $template['action_label'],
                        'context' => [
                            'demo_seed' => true,
                            'demo_key' => $template['key'],
                            'seeded_for_user_id' => $user->id,
                        ],
                    ],
                    // Mix read and unread notifications so the bell and filters can be reviewed.
                    'read_at' => $notificationIndex >= 3
                        ? Carbon::parse($createdAt)->addMinutes(20)
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $this->command?->info(
                sprintf(
                    'Created %d demo notifications for %s (user ID %d).',
                    $userTemplates->count(),
                    $user->name,
                    $user->id,
                )
            );
        }

        $this->command?->newLine();
        $this->command?->info(
            sprintf(
                'Created demo notifications for %d user(s). No emails were sent.',
                $users->count(),
            )
        );
    }

    /**
     * Remove only notifications previously created by this demo seeder.
     *
     * @param Collection<int, User> $users
     */
    private function removeExistingDemoNotifications(Collection $users): void
    {
        foreach ($users as $user) {
            $user->notifications()
                ->where('type', CustomerAccountNotification::class)
                ->get()
                ->filter(
                    fn ($notification): bool =>
                        (bool) data_get($notification->data, 'context.demo_seed', false)
                )
                ->each
                ->delete();
        }
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     category: string,
     *     title: string,
     *     message: string,
     *     action_url: string,
     *     action_label: string
     * }>
     */
    private function templates(): array
    {
        return [
            [
                'key' => 'order_paid',
                'category' => 'orders',
                'title' => 'Payment received',
                'message' => 'Your order UC-10428 has been paid successfully and is being prepared.',
                'action_url' => '/account/library',
                'action_label' => 'View order',
            ],
            [
                'key' => 'download_ready',
                'category' => 'fulfillment',
                'title' => 'Your files are ready',
                'message' => 'The files from your recent purchase are now ready to download.',
                'action_url' => '/account/library',
                'action_label' => 'View downloads',
            ],
            [
                'key' => 'license_expiring',
                'category' => 'licenses',
                'title' => 'License expires soon',
                'message' => 'One of your licenses will expire in 14 days. Review the license details and permitted usage.',
                'action_url' => '/account/library?status=expiring',
                'action_label' => 'Review license',
            ],
            [
                'key' => 'download_limit',
                'category' => 'downloads',
                'title' => 'One download remaining',
                'message' => 'You have one download remaining for a licensed asset.',
                'action_url' => '/account/library',
                'action_label' => 'View license',
            ],
            [
                'key' => 'security',
                'category' => 'security',
                'title' => 'New account sign-in',
                'message' => 'A new sign-in to your account was recorded. Review your security settings if this was not you.',
                'action_url' => '/account/security',
                'action_label' => 'Review security',
            ],
            [
                'key' => 'recommendations',
                'category' => 'discovery',
                'title' => 'New assets selected for you',
                'message' => 'We found several new assets that match your recent browsing interests.',
                'action_url' => '/account',
                'action_label' => 'See recommendations',
            ],
            [
                'key' => 'wish_list_available',
                'category' => 'wish_lists',
                'title' => 'A saved asset is available again',
                'message' => 'An asset in your Favorites list is available for purchase again.',
                'action_url' => '/account/wish-lists',
                'action_label' => 'View wish lists',
            ],
            [
                'key' => 'refund',
                'category' => 'orders',
                'title' => 'Refund completed',
                'message' => 'Your refund for order UC-10391 has been completed.',
                'action_url' => '/account/library',
                'action_label' => 'View purchase',
            ],
            [
                'key' => 'tracking',
                'category' => 'fulfillment',
                'title' => 'Order shipped',
                'message' => 'Your order has shipped. Tracking information is now available.',
                'action_url' => '/account/library',
                'action_label' => 'View tracking',
            ],
            [
                'key' => 'license_updated',
                'category' => 'licenses',
                'title' => 'License information updated',
                'message' => 'Important information has been added to one of your license records.',
                'action_url' => '/account/library',
                'action_label' => 'View license',
            ],
        ];
    }
}
