<?php

use App\Models\PublicPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('renders published public page content through the shared rich content renderer', function (): void {
    $page = PublicPage::factory()
        ->published()
        ->create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => '<p><img src="/example.jpg" class="blog-image-left blog-image-medium" alt="Example">Wrapped copy.</p>',
            'published_at' => now()->subMinute(),
        ]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertInertia(fn (Assert $response) => $response
            ->component('PublicPages/Show')
            ->where('publicPage.content', $page->content));
});

it('uses the shared blog content class in the public page template', function (): void {
    $template = file_get_contents(resource_path('js/pages/PublicPages/Show.vue'));

    expect($template)
        ->toContain('blog-content public-rich-content');
});
