<?php

use App\Models\Permission;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Services\Support\SupportReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'view_admin'], ['label' => 'View admin']);
    Permission::firstOrCreate(['name' => 'view_support_tickets'], ['label' => 'View support tickets']);
    Permission::firstOrCreate(['name' => 'view_support_reports'], ['label' => 'View support reports']);
    Permission::firstOrCreate(['name' => 'manage_support_tickets'], ['label' => 'Manage support tickets']);
});

it('calculates support reporting metrics', function (): void {
    SupportTicket::factory()->count(2)->create([
        'created_at' => now()->subDays(2),
        'first_response_at' => now()->subDays(2)->addMinutes(30),
        'resolved_at' => now()->subDay(),
    ]);

    $report = app(SupportReportingService::class)->report(now()->subDays(30), now());

    expect($report['summary']['created'])->toBe(2)
        ->and($report['summary']['resolved'])->toBe(2)
        ->and($report['summary']['average_first_response_minutes'])->toBe(30.0);
});

it('protects support reports with the reporting permission', function (): void {
    $user = User::factory()->create();
    $user->permissions()->attach(Permission::where('name', 'view_admin')->first());
    $user->permissions()->attach(Permission::where('name', 'view_support_tickets')->first());

    $this->actingAs($user)->get('/admin/support/reports')->assertForbidden();

    $user->permissions()->attach(Permission::where('name', 'view_support_reports')->first());
    $this->actingAs($user->fresh())->get('/admin/support/reports')->assertOk();
});

it('redacts attachment storage and metadata', function (): void {
    Storage::fake('local');

    $user = User::factory()->create();
    $user->permissions()->attach(Permission::where('name', 'view_admin')->first());
    $user->permissions()->attach(Permission::where('name', 'view_support_tickets')->first());
    $user->permissions()->attach(Permission::where('name', 'manage_support_tickets')->first());

    $ticket = SupportTicket::factory()->create();
    Storage::disk('local')->put('support/test.txt', 'secret');
    $attachment = SupportTicketAttachment::create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'support_ticket_id' => $ticket->id,
        'disk' => 'local',
        'path' => 'support/test.txt',
        'original_filename' => 'test.txt',
        'mime_type' => 'text/plain',
        'extension' => 'txt',
        'size_bytes' => 6,
        'checksum_sha256' => hash('sha256', 'secret'),
        'scan_status' => 'clean',
        'is_customer_visible' => true,
    ]);

    $this->actingAs($user)->delete(
        route('admin.support.tickets.attachments.redact', [$ticket, $attachment]),
        ['reason' => 'Contains sensitive information.'],
    )->assertRedirect();

    expect($attachment->fresh()->redacted_at)->not->toBeNull()
        ->and($attachment->fresh()->is_customer_visible)->toBeFalse();
    Storage::disk('local')->assertMissing('support/test.txt');
});
