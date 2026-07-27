<?php

use App\Models\PublicPage;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    SupportTicketCategory::factory()->create([
        'name' => 'Download problem',
        'is_active' => true,
        'is_public' => true,
        'is_member' => true,
    ]);
});

it('renders the public support landing page with the fallback content and categories', function (): void {
    $this->get('/support')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Support/Landing')
            ->where('supportPage.title', 'Support Center')
            ->has('categories', 1));
});

it('uses a published support mini cms page for the support landing page', function (): void {
    $page = PublicPage::factory()->published()->create([
        'title' => 'How We Can Help',
        'slug' => 'support-page-content',
        'page_type' => PublicPage::TYPE_SUPPORT,
        'content' => '<p>Custom support introduction.</p>',
    ]);
    $page->faqItems()->create([
        'question' => 'Can guests submit?',
        'answer' => 'Yes.',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $this->get('/support')
        ->assertOk()
        ->assertInertia(fn (Assert $response) => $response
            ->where('supportPage.title', 'How We Can Help')
            ->where('supportPage.content', '<p>Custom support introduction.</p>')
            ->has('supportPage.faq_items', 1));
});

it('embeds the guest request form data on the public support page', function (): void {
    $category = SupportTicketCategory::firstOrFail();

    $this->get('/support?category_id='.$category->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Support/Landing')
            ->where('mode', 'guest')
            ->where('initialCategoryId', $category->id)
            ->has('attachmentRules'));
});

it('embeds the member request form data while keeping the public layout', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $category = SupportTicketCategory::firstOrFail();

    $this->actingAs($user)
        ->get('/support?category_id='.$category->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Support/Landing')
            ->where('mode', 'member')
            ->where('isAuthenticated', true)
            ->where('initialCategoryId', $category->id));
});

it('redirects legacy create routes to the embedded support form', function (): void {
    $category = SupportTicketCategory::firstOrFail();

    $this->get('/support/create?category_id='.$category->id)
        ->assertRedirect('/support?category_id='.$category->id.'#submit-request');

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/support/tickets/create?category_id='.$category->id)
        ->assertRedirect('/support?category_id='.$category->id.'#submit-request');
});

it('redirects the support content slug to the canonical support route', function (): void {
    PublicPage::factory()->published()->create([
        'slug' => 'support-page-content',
        'page_type' => PublicPage::TYPE_SUPPORT,
    ]);

    $this->get('/support-page-content')
        ->assertStatus(301)
        ->assertRedirect('/support');
});

it('shares selected support navigation locations using the canonical support route', function (): void {
    PublicPage::factory()->published()->create([
        'title' => 'Support Center',
        'slug' => 'support-page-content',
        'page_type' => PublicPage::TYPE_SUPPORT,
        'navigation_label' => 'Support',
        'navigation_locations' => [
            PublicPage::NAV_HEADER,
            PublicPage::NAV_FOOTER_RESOURCES,
        ],
        'sort_order' => 1,
    ]);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('public_page_navigation.header.0.label', 'Support')
            ->where('public_page_navigation.header.0.href', '/support')
            ->where('public_page_navigation.footer_resources.0.href', '/support'));
});
