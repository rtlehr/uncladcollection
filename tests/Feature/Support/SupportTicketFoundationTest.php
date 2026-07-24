<?php

use App\Enums\SupportTicketMessageType;
use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\Asset;
use App\Models\Permission;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use App\Services\Support\GuestTicketAccessService;
use App\Services\Support\SupportTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a member ticket with a readable unique number and opening message', function () {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->create(['default_priority' => SupportTicketPriority::High]);

    $ticket = app(SupportTicketService::class)->createForMember($user, [
        'category_id' => $category->id,
        'subject' => 'My download is unavailable',
        'description' => 'The purchased file cannot be downloaded.',
    ]);

    expect($ticket->ticket_number)->toStartWith('UC-')
        ->and($ticket->user_id)->toBe($user->id)
        ->and($ticket->priority)->toBe(SupportTicketPriority::High)
        ->and($ticket->messages)->toHaveCount(1)
        ->and($ticket->messages->first()->message_type)->toBe(SupportTicketMessageType::CustomerMessage);
});

it('stores only a hash for guest access and validates the issued token', function () {
    $result = app(SupportTicketService::class)->createForGuest([
        'guest_name' => 'Guest Customer',
        'guest_email' => 'guest@example.test',
        'subject' => 'Account question',
        'description' => 'I need help finding an earlier purchase.',
    ]);

    $ticket = $result['ticket'];
    $token = $result['token'];

    expect($token)->not->toBe('')
        ->and($ticket->getRawOriginal('guest_access_token_hash'))->toBe(hash('sha256', $token))
        ->and(app(GuestTicketAccessService::class)->validate($ticket, $token))->toBeTrue()
        ->and(app(GuestTicketAccessService::class)->validate($ticket, 'incorrect'))->toBeFalse();
});

it('protects member ownership while allowing permitted support staff', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $staff = User::factory()->create();
    $permission = Permission::query()->create([
        'name' => 'view_support_tickets', 'label' => 'View Support Tickets',
        'group_name' => 'Support', 'description' => 'Test permission.',
    ]);
    $staff->permissions()->attach($permission);
    $ticket = SupportTicket::factory()->for($owner)->create();

    expect($owner->can('view', $ticket))->toBeTrue()
        ->and($other->can('view', $ticket))->toBeFalse()
        ->and($staff->can('view', $ticket))->toBeTrue();
});

it('never includes internal notes in the customer visible message relationship', function () {
    $staff = User::factory()->create();
    $ticket = SupportTicket::factory()->create();

    app(SupportTicketService::class)->addInternalNote($ticket, $staff, 'Private investigation details.');
    app(SupportTicketService::class)->addStaffReply($ticket, $staff, 'We are reviewing the issue.');

    expect($ticket->customerVisibleMessages()->count())->toBe(1)
        ->and($ticket->messages()->where('message_type', SupportTicketMessageType::InternalNote)->first()->is_customer_visible)->toBeFalse();
});

it('records assignment priority and status history', function () {
    $ticket = SupportTicket::factory()->create(['status' => SupportTicketStatus::New]);
    $staff = User::factory()->create();
    $service = app(SupportTicketService::class);

    $service->assign($ticket, $staff, $staff);
    $service->changePriority($ticket, SupportTicketPriority::Urgent, $staff);
    $service->transition($ticket, SupportTicketStatus::InProgress, $staff);

    expect($ticket->refresh()->assigned_user_id)->toBe($staff->id)
        ->and($ticket->priority)->toBe(SupportTicketPriority::Urgent)
        ->and($ticket->status)->toBe(SupportTicketStatus::InProgress)
        ->and($ticket->statusHistories()->count())->toBe(3);
});

it('rejects invalid status transitions and unsupported relation types', function () {
    $ticket = SupportTicket::factory()->create(['status' => SupportTicketStatus::Closed]);

    expect(fn () => app(SupportTicketService::class)->transition($ticket, SupportTicketStatus::Open))
        ->toThrow(ValidationException::class);

    $unsupported = SupportTicketCategory::factory()->create();
    expect(fn () => app(SupportTicketService::class)->relate($ticket, $unsupported))
        ->toThrow(ValidationException::class);
});

it('links an allowed marketplace record only once', function () {
    $ticket = SupportTicket::factory()->create();
    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Support Test Asset',
        'slug' => 'support-test-asset-'.Str::lower(Str::random(8)),
        'asset_type' => 'image',
        'status' => 'draft',
        'is_active' => true,
    ]);

    $service = app(SupportTicketService::class);
    $service->relate($ticket, $asset);
    $service->relate($ticket, $asset);

    expect($ticket->relations()->count())->toBe(1)
        ->and($ticket->relations()->first()->related->is($asset))->toBeTrue();
});
