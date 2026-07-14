<?php

use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('selects only an active scheduled marketing campaign for the homepage', function () {
    Storage::fake('public');
    MarketingCampaign::query()->create([
        'uuid' => fake()->uuid(), 'name' => 'Welcome', 'media_type' => 'video',
        'media_path' => UploadedFile::fake()->create('welcome.mp4', 20, 'video/mp4')->store('marketing/campaigns/test', 'public'),
        'headline' => 'Welcome to Unclad Collection', 'overlay_opacity' => 35,
        'media_position' => 'center', 'hero_height' => 'large', 'text_alignment' => 'left',
        'autoplay_first_visit' => true, 'autoplay_mobile' => false, 'loop_video' => true,
        'show_search' => true, 'is_active' => true, 'sort_order' => 0,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
    ]);

    $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->where('heroCampaign.name', 'Welcome')
        ->where('heroCampaign.media_type', 'video'));
});
