<?php

namespace Tests\Feature\Advertising;

use App\Enums\AnalyticsEventName;
use App\Models\{AdCreative, AdPlacement, Advertiser, AdvertisingCampaign, AnalyticsEvent};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicAdDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_creative_is_delivered_and_tracked(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('advertising/test/rendered/ad.jpg', 'image');

        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create(['code' => 'homepage-below-hero', 'width' => 1200, 'height' => 300, 'is_active' => true]);
        $campaign = AdvertisingCampaign::factory()->create(['advertiser_id' => $advertiser->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $campaign->placements()->attach($placement->id, ['priority' => 5]);
        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id, 'ad_placement_id' => $placement->id,
            'status' => 'approved', 'creative_type' => 'image', 'media_path' => 'advertising/test/rendered/ad.jpg',
            'width' => 1200, 'height' => 300, 'destination_url' => 'https://example.com',
        ]);

        $this->getJson('/ads/placements/homepage-below-hero')->assertOk()->assertJsonPath('creative.id', $creative->id);
        $this->postJson("/ads/creatives/{$creative->id}/impression", ['placement_code' => $placement->code])->assertOk();
        $this->postJson("/ads/creatives/{$creative->id}/click", ['placement_code' => $placement->code])->assertOk();

        $this->assertDatabaseHas('analytics_events', ['event_name' => AnalyticsEventName::AdvertisingImpression->value, 'subject_id' => $creative->id]);
        $this->assertDatabaseHas('analytics_events', ['event_name' => AnalyticsEventName::AdvertisingClicked->value, 'subject_id' => $creative->id]);
    }

    public function test_ineligible_or_empty_placement_returns_no_content(): void
    {
        AdPlacement::factory()->create(['code' => 'empty-placement', 'is_active' => true]);
        $this->getJson('/ads/placements/empty-placement')->assertNoContent();
    }
}
