<?php

use App\Models\SupportTicketCategory;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    SupportTicketCategory::factory()->create([
        'is_active' => true,
        'is_public' => true,
        'is_member' => true,
    ]);
});

it('returns guests to the public support page with a confirmation message', function (): void {
    Notification::fake();

    $this->post(route('support.store'), [
        'guest_name' => 'Guest User',
        'guest_email' => 'guest@example.com',
        'category_id' => SupportTicketCategory::firstOrFail()->id,
        'subject' => 'Download problem',
        'description' => 'The download did not start.',
    ])
        ->assertRedirect('/support#submit-request')
        ->assertSessionHas('support_success.title', 'Your ticket has been submitted.')
        ->assertSessionHas('support_success.show_tickets_link', false);

    Notification::assertSentOnDemand(SupportTicketCreatedNotification::class);
});

it('returns members to the public support page with a my tickets action', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('support.member.store'), [
            'category_id' => SupportTicketCategory::firstOrFail()->id,
            'subject' => 'License question',
            'description' => 'Please explain this license.',
        ])
        ->assertRedirect('/support#submit-request')
        ->assertSessionHas('support_success.title', 'Your ticket has been submitted.')
        ->assertSessionHas('support_success.show_tickets_link', true);
});

it('renders the front-page confirmation and my tickets action', function (): void {
    $template = file_get_contents(
        resource_path('js/pages/Support/Landing.vue'),
    );

    expect($template)
        ->toContain('v-if="supportSuccess"')
        ->toContain('{{ supportSuccess.title }}')
        ->toContain('{{ supportSuccess.message }}')
        ->toContain('View my tickets')
        ->toContain('role="status"')
        ->toContain('aria-live="polite"');
});
