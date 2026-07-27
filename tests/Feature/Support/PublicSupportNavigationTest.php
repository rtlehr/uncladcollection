<?php

use App\Models\Permission;
use App\Models\PublicPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function supportNavigationPageAdmin(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    foreach ([
        ['view_admin', 'View Admin'],
        ['manage_public_pages', 'Manage Public Pages'],
        ['publish_public_pages', 'Publish Public Pages'],
    ] as [$name, $label]) {
        $permission = Permission::firstOrCreate(
            ['name' => $name],
            [
                'label' => $label,
                'group_name' => 'Content',
                'description' => $label,
            ],
        );

        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    return $user;
}

it('persists navigation locations for support pages', function (): void {
    $user = supportNavigationPageAdmin();

    $page = PublicPage::factory()->published()->create([
        'title' => 'Support Center',
        'slug' => 'support-page-content',
        'page_type' => PublicPage::TYPE_SUPPORT,
        'navigation_locations' => [],
    ]);

    $this->actingAs($user)
        ->put('/admin/public-pages/'.$page->slug, [
            'title' => $page->title,
            'slug' => $page->slug,
            'page_type' => PublicPage::TYPE_SUPPORT,
            'status' => PublicPage::STATUS_PUBLISHED,
            'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'is_active' => true,
            'sort_order' => 10,
            'navigation_label' => 'Get Support',
            'navigation_locations' => [
                PublicPage::NAV_HEADER,
                PublicPage::NAV_FOOTER_RESOURCES,
            ],
            'faq_items' => [],
        ])
        ->assertRedirect('/admin/public-pages');

    expect($page->fresh()->navigation_locations)->toBe([
        PublicPage::NAV_HEADER,
        PublicPage::NAV_FOOTER_RESOURCES,
    ]);
});

it('shows navigation controls for the support page type', function (): void {
    $template = file_get_contents(
        resource_path('js/components/admin/PublicPageForm.vue'),
    );

    expect($template)
        ->not->toContain("v-if=\"form.page_type !== 'support'\"")
        ->not->toContain("props.form.navigation_locations = []")
        ->toContain('Support Center links always open /support.');
});
