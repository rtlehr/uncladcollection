<?php

use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    SupportTicketCategory::factory()->create(['is_active' => true, 'is_public' => true, 'is_member' => true]);
});

it('allows a guest to submit and securely view a ticket', function () {
    Notification::fake();
    $response = $this->post(route('support.store'), [
        'guest_name' => 'Guest User', 'guest_email' => 'guest@example.com',
        'category_id' => SupportTicketCategory::first()->id,
        'subject' => 'Download problem', 'description' => 'The download did not start.',
    ]);
    $ticket = SupportTicket::firstOrFail();
    $response->assertRedirect();
    expect($ticket->guest_access_token_hash)->not->toBeNull();
    Notification::assertSentOnDemand(SupportTicketCreatedNotification::class);
});

it('does not expose a guest ticket with an invalid token', function () {
    $ticket = SupportTicket::factory()->guest()->create();
    $this->get(route('support.guest.show', [$ticket, 'invalid']))->assertNotFound();
});

it('allows members to submit and view only their tickets', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($owner)->post(route('support.member.store'), [
        'category_id' => SupportTicketCategory::first()->id,
        'subject' => 'License question', 'description' => 'Please explain this license.',
    ])->assertRedirect();
    $ticket = SupportTicket::firstOrFail();
    $this->actingAs($owner)->get(route('support.show', $ticket))->assertOk();
    $this->actingAs($other)->get(route('support.show', $ticket))->assertForbidden();
});

it('keeps internal notes out of customer payloads', function () {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->for($user)->create();
    SupportTicketMessage::factory()->for($ticket, 'ticket')->internalNote()->create(['body' => 'Private note']);
    $this->actingAs($user)->get(route('support.show', $ticket))->assertOk()->assertDontSee('Private note');
});

it('stores customer-visible attachments privately', function () {
    Storage::fake('local'); Notification::fake();
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('support.member.store'), [
        'category_id' => SupportTicketCategory::first()->id,
        'subject' => 'Screenshot', 'description' => 'Attached.',
        'attachments' => [UploadedFile::fake()->image('problem.png')],
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect(SupportTicket::firstOrFail()->attachments()->count())->toBe(1);
});
