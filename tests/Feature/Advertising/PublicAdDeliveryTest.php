<?php

namespace Tests\Feature\Advertising;

use App\Enums\AnalyticsEventName;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Models\Advertiser;
use App\Models\AdvertisingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicAdDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_eligible_creative_is_delivered_and_tracked(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('advertising/test/rendered/ad.jpg', 'image');

        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create([
            'code' => 'homepage-below-hero',
            'width' => 1200,
            'height' => 300,
            'is_active' => true,
        ]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        $campaign->placements()->attach($placement->id, ['priority' => 5]);
        $creative = $this->createCreative($campaign, $placement, 'advertising/test/rendered/ad.jpg');

        $this->getJson('/ads/placements/homepage-below-hero')
            ->assertOk()
            ->assertJsonPath('creative.id', $creative->id);

        $this->postJson("/ads/creatives/{$creative->id}/impression", [
            'placement_code' => $placement->code,
        ])->assertOk();

        $this->postJson("/ads/creatives/{$creative->id}/click", [
            'placement_code' => $placement->code,
        ])->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => AnalyticsEventName::AdvertisingImpression->value,
            'subject_id' => $creative->id,
        ]);
        $this->assertDatabaseHas('analytics_events', [
            'event_name' => AnalyticsEventName::AdvertisingClicked->value,
            'subject_id' => $creative->id,
        ]);
    }

    public function test_one_creative_can_be_delivered_to_multiple_compatible_placements(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('advertising/test/rendered/shared.jpg', 'image');

        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $first = AdPlacement::factory()->create([
            'code' => 'homepage-below-hero',
            'width' => 1200,
            'height' => 300,
            'is_active' => true,
        ]);
        $second = AdPlacement::factory()->create([
            'code' => 'asset-gallery-inline',
            'width' => 1200,
            'height' => 300,
            'is_active' => true,
        ]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        $campaign->placements()->attach([
            $first->id => ['priority' => 5],
            $second->id => ['priority' => 5],
        ]);

        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'ad_placement_id' => $first->id,
            'status' => 'approved',
            'creative_type' => 'image',
            'media_path' => 'advertising/test/rendered/shared.jpg',
            'width' => 1200,
            'height' => 300,
            'destination_url' => 'https://example.com',
        ]);
        $creative->placements()->attach([$first->id, $second->id]);

        $this->getJson('/ads/placements/homepage-below-hero')
            ->assertOk()
            ->assertJsonPath('creative.id', $creative->id);
        $this->getJson('/ads/placements/asset-gallery-inline')
            ->assertOk()
            ->assertJsonPath('creative.id', $creative->id);
    }


    public function test_public_page_placements_can_deliver_ads(): void
    {
        Storage::fake('public');
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        foreach ([
            ['code' => 'public-page-after-content', 'width' => 760, 'height' => 240, 'file' => 'public-page-banner.jpg'],
            ['code' => 'public-page-sidebar', 'width' => 300, 'height' => 250, 'file' => 'public-page-sidebar.jpg'],
        ] as $data) {
            $placement = AdPlacement::factory()->create([
                'code' => $data['code'],
                'width' => $data['width'],
                'height' => $data['height'],
                'is_active' => true,
            ]);
            $campaign->placements()->attach($placement->id, ['priority' => 50]);

            $path = 'advertising/test/rendered/'.$data['file'];
            Storage::disk('public')->put($path, 'image');
            $creative = AdCreative::factory()->create([
                'advertising_campaign_id' => $campaign->id,
                'ad_placement_id' => $placement->id,
                'status' => 'approved',
                'creative_type' => 'image',
                'media_path' => $path,
                'width' => $data['width'],
                'height' => $data['height'],
                'destination_url' => 'https://example.com/'.$data['code'],
            ]);
            $creative->placements()->attach($placement->id);

            $this->getJson('/ads/placements/'.$data['code'])
                ->assertOk()
                ->assertJsonPath('creative.id', $creative->id)
                ->assertJsonPath('placement.code', $data['code']);
        }
    }

    public function test_ineligible_or_empty_placement_returns_no_content(): void
    {
        AdPlacement::factory()->create(['code' => 'empty-placement', 'is_active' => true]);
        $this->getJson('/ads/placements/empty-placement')->assertNoContent();
    }

    public function test_equal_weight_campaigns_do_not_repeat_more_than_two_times_in_a_row(): void
    {
        Storage::fake('public');
        $placement = $this->placement();

        [$firstCampaign] = $this->campaignWithCreative($placement, 50, 'first.jpg');
        [$secondCampaign] = $this->campaignWithCreative($placement, 50, 'second.jpg');

        $sequence = $this->campaignSequence($placement, 12);

        $this->assertContains($firstCampaign->id, $sequence);
        $this->assertContains($secondCampaign->id, $sequence);
        $this->assertLessThanOrEqual(2, $this->longestRun($sequence));
    }

    public function test_rotation_weight_still_influences_delivery_share(): void
    {
        Storage::fake('public');
        $placement = $this->placement();

        [$heavyCampaign] = $this->campaignWithCreative($placement, 75, 'heavy.jpg');
        [$lightCampaign] = $this->campaignWithCreative($placement, 25, 'light.jpg');

        $sequence = $this->campaignSequence($placement, 24);
        $counts = array_count_values($sequence);

        $this->assertGreaterThan($counts[$lightCampaign->id] ?? 0, $counts[$heavyCampaign->id] ?? 0);
        $this->assertContains($lightCampaign->id, $sequence);
        $this->assertLessThanOrEqual(2, $this->longestRun($sequence));
    }

    public function test_low_weight_campaigns_are_protected_from_starvation(): void
    {
        Storage::fake('public');
        $placement = $this->placement();

        [$dominant] = $this->campaignWithCreative($placement, 100, 'dominant.jpg');
        [$smallOne] = $this->campaignWithCreative($placement, 1, 'small-one.jpg');
        [$smallTwo] = $this->campaignWithCreative($placement, 1, 'small-two.jpg');

        $sequence = $this->campaignSequence($placement, 12);

        $this->assertContains($dominant->id, $sequence);
        $this->assertContains($smallOne->id, $sequence);
        $this->assertContains($smallTwo->id, $sequence);
        $this->assertLessThanOrEqual(2, $this->longestRun($sequence));
    }

    public function test_campaign_with_more_creatives_does_not_receive_extra_campaign_weight(): void
    {
        Storage::fake('public');
        $placement = $this->placement();

        [$multiCampaign] = $this->campaignWithCreative($placement, 50, 'multi-a.jpg');
        $this->createCreative($multiCampaign, $placement, 'multi-b.jpg');
        $this->createCreative($multiCampaign, $placement, 'multi-c.jpg');

        [$singleCampaign] = $this->campaignWithCreative($placement, 50, 'single.jpg');

        $sequence = $this->campaignSequence($placement, 20);
        $counts = array_count_values($sequence);

        $difference = abs(($counts[$multiCampaign->id] ?? 0) - ($counts[$singleCampaign->id] ?? 0));
        $this->assertLessThanOrEqual(2, $difference);
    }

    public function test_creatives_inside_one_campaign_are_rotated_instead_of_randomly_starved(): void
    {
        Storage::fake('public');
        $placement = $this->placement();

        [$campaign, $firstCreative] = $this->campaignWithCreative($placement, 50, 'creative-a.jpg');
        $secondCreative = $this->createCreative($campaign, $placement, 'creative-b.jpg');
        $thirdCreative = $this->createCreative($campaign, $placement, 'creative-c.jpg');

        $creativeIds = [];
        for ($i = 0; $i < 6; $i++) {
            $creativeIds[] = $this->getJson('/ads/placements/'.$placement->code)
                ->assertOk()
                ->json('creative.id');
        }

        $this->assertContains($firstCreative->id, $creativeIds);
        $this->assertContains($secondCreative->id, $creativeIds);
        $this->assertContains($thirdCreative->id, $creativeIds);
    }

    private function placement(): AdPlacement
    {
        return AdPlacement::factory()->create([
            'code' => 'rotation-test',
            'width' => 1200,
            'height' => 300,
            'is_active' => true,
        ]);
    }

    private function campaignWithCreative(AdPlacement $placement, int $weight, string $filename): array
    {
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);
        $campaign->placements()->attach($placement->id, ['priority' => $weight]);
        $creative = $this->createCreative($campaign, $placement, $filename);

        return [$campaign, $creative];
    }

    private function createCreative(AdvertisingCampaign $campaign, AdPlacement $placement, string $filename): AdCreative
    {
        $path = 'advertising/test/rendered/'.$filename;
        Storage::disk('public')->put($path, 'image');

        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'ad_placement_id' => $placement->id,
            'status' => 'approved',
            'creative_type' => 'image',
            'media_path' => $path,
            'width' => 1200,
            'height' => 300,
            'destination_url' => 'https://example.com/'.$filename,
        ]);
        $creative->placements()->attach($placement->id);

        return $creative;
    }

    private function campaignSequence(AdPlacement $placement, int $requests): array
    {
        $sequence = [];

        for ($i = 0; $i < $requests; $i++) {
            $sequence[] = (int) $this->getJson('/ads/placements/'.$placement->code)
                ->assertOk()
                ->json('campaign.id');
        }

        return $sequence;
    }

    private function longestRun(array $values): int
    {
        $longest = 0;
        $current = 0;
        $previous = null;

        foreach ($values as $value) {
            if ($value === $previous) {
                $current++;
            } else {
                $previous = $value;
                $current = 1;
            }

            $longest = max($longest, $current);
        }

        return $longest;
    }
}
