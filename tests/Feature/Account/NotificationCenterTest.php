<?php

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CustomerAccountNotification;
use App\Services\Notifications\CustomerNotificationService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

it('shows only the signed in customers notifications', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);

    $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => CustomerAccountNotification::class, 'data' => ['category' => 'orders', 'title' => 'Your order', 'message' => 'Ready']]);
    $other->notifications()->create(['id' => (string) Str::uuid(), 'type' => CustomerAccountNotification::class, 'data' => ['category' => 'orders', 'title' => 'Other order', 'message' => 'Private']]);

    $this->actingAs($user)->get(route('account.notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Account/Notifications/Index')->has('notifications.data', 1)->where('notifications.data.0.title', 'Your order'));
});

it('does not let a customer mark another customers notification as read', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $notification = $other->notifications()->create(['id' => (string) Str::uuid(), 'type' => CustomerAccountNotification::class, 'data' => ['title' => 'Private', 'message' => 'Private']]);

    $this->actingAs($user)->patch(route('account.notifications.read', $notification))->assertNotFound();
});

it('stores optional notification preferences', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->put(route('account.notification-preferences.update'), [
        'preferences' => [[
            'category' => 'discovery',
            'in_app_enabled' => true,
            'email_enabled' => false,
            'email_frequency' => 'off',
        ]],
    ])->assertRedirect();

    $this->assertDatabaseHas('notification_preferences', ['user_id' => $user->id, 'category' => 'discovery', 'in_app_enabled' => 1, 'email_enabled' => 0, 'email_frequency' => 'off']);
});

it('keeps essential transactional email notifications enabled', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->put(route('account.notification-preferences.update'), [
        'preferences' => [[
            'category' => 'orders',
            'in_app_enabled' => false,
            'email_enabled' => false,
            'email_frequency' => 'off',
        ]],
    ])->assertRedirect();

    $preference = NotificationPreference::query()->where('user_id', $user->id)->where('category', 'orders')->firstOrFail();
    expect($preference->email_enabled)->toBeTrue()->and($preference->email_frequency)->toBe('immediate');
});

it('dispatches customer notifications through the preference service', function () {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => now()]);

    app(CustomerNotificationService::class)->send($user, 'orders', 'Order paid', 'Your order is ready.', route('account.library.index'));

    Notification::assertSentTo($user, CustomerAccountNotification::class, fn ($notification) => $notification->title === 'Order paid');
});
