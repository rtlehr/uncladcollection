<?php

use App\Models\SupportTicketCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the secure request form directly on the public support page', function (): void {
    SupportTicketCategory::factory()->create([
        'is_active' => true,
        'is_public' => true,
        'is_member' => true,
    ]);

    $template = file_get_contents(
        resource_path('js/pages/Support/Landing.vue'),
    );

    expect($template)
        ->toContain('id="submit-request"')
        ->toContain('<PublicSupportRequestForm')
        ->toContain('@click="chooseCategory(category.id)"')
        ->not->toContain('/support/create?category_id=');
});

it('keeps the existing guest ticket submission endpoint', function (): void {
    $template = file_get_contents(
        resource_path('js/components/Support/PublicSupportRequestForm.vue'),
    );

    expect($template)
        ->toContain("props.mode === 'guest' ? '/support' : '/support/tickets'")
        ->toContain('forceFormData: true');
});
