<?php

use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Notifications\VerifyMembershipEmail;
use App\Notifications\WelcomeMembershipEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends the editable membership verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, VerifyMembershipEmail::class);
});

it('renders and sends the verification email through the communications template', function () {
    $user = User::factory()->unverified()->create();

    $notification = new VerifyMembershipEmail;
    $mail = $notification->toMail($user);

    expect($mail->subject)
        ->toBe('Confirm your Unclad Collection membership')
        ->and($notification->deliveryLogId)->not->toBeNull();

    expect(EmailDeliveryLog::query()->find($notification->deliveryLogId))
        ->status->toBe('queued')
        ->template_key->toBe('account.verify_email');
});

it('sends the welcome communication after verification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    event(new Verified($user));

    Notification::assertSentTo($user, WelcomeMembershipEmail::class);
});

it('keeps protected customer pages unavailable until verification', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('account.index'))
        ->assertRedirect(route('verification.notice'));
});

it('allows a verified member to access the customer account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('account.index'))
        ->assertSuccessful();
});
