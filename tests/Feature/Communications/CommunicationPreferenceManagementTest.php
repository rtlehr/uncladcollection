<?php

use App\Models\CommunicationPreferenceChange;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Communications\CommunicationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('records customer consent changes from the account page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)->put(route('account.notification-preferences.update'), [
        'preferences' => [[
            'category' => 'discovery',
            'in_app_enabled' => true,
            'email_enabled' => true,
            'email_frequency' => 'immediate',
        ]],
    ])->assertRedirect();

    expect(CommunicationPreferenceChange::query()
        ->where('user_id', $user->id)
        ->where('category', 'discovery')
        ->where('channel', 'email')
        ->where('source', 'account')
        ->exists())->toBeTrue();
});

it('allows a customer to unsubscribe from an optional category with a signed link', function () {
    $user = User::factory()->create();
    NotificationPreference::query()->create([
        'user_id' => $user->id,
        'category' => 'discovery',
        'in_app_enabled' => true,
        'email_enabled' => true,
        'email_frequency' => 'immediate',
    ]);

    $url = URL::temporarySignedRoute('communications.unsubscribe.store', now()->addMinutes(10), [
        'user' => $user->id,
        'category' => 'discovery',
    ]);

    $this->post($url)->assertRedirect(route('communications.unsubscribe.confirmed'));

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'category' => 'discovery',
        'email_enabled' => 0,
        'email_frequency' => 'off',
    ]);

    $this->assertDatabaseHas('communication_preference_changes', [
        'user_id' => $user->id,
        'category' => 'discovery',
        'channel' => 'email',
        'source' => 'unsubscribe_link',
        'new_value' => 0,
    ]);
});

it('does not provide unsubscribe links for required communications', function () {
    $user = User::factory()->create();

    expect(app(CommunicationPreferenceService::class)->unsubscribeUrl($user, 'orders'))->toBeNull();
});

it('rejects unsigned preference changes', function () {
    $user = User::factory()->create();

    $this->post("/communications/unsubscribe/{$user->id}/discovery")->assertForbidden();
});
