<?php

namespace Tests\Feature\Advertising;

use App\Models\{AdCreative, AdPlacement, Advertiser, AdvertisingCampaign};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisingCampaignLifecycleAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_scheduled_campaign_activates_automatically_when_still_launch_ready(): void
    {
        Storage::fake('public');

        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create(['is_active' => true]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'scheduled',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->placements()->attach($placement);

        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'status' => 'approved',
            'media_path' => 'marketing/test/auto-activate.jpg',
            'destination_url' => 'https://example.com',
        ]);
        $creative->placements()->attach($placement);
        Storage::disk('public')->put('marketing/test/auto-activate.jpg', 'image-bytes');

        Artisan::call('advertising:sync-campaign-statuses');

        $this->assertSame('active', $campaign->fresh()->status);
        $this->assertDatabaseHas('admin_activities', [
            'subject_type' => AdvertisingCampaign::class,
            'subject_id' => $campaign->id,
            'field_name' => 'status',
            'old_value' => 'scheduled',
            'new_value' => 'active',
            'user_id' => null,
        ]);
    }

    public function test_due_scheduled_campaign_stays_scheduled_when_launch_readiness_is_blocked(): void
    {
        Storage::fake('public');

        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create(['is_active' => true]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'scheduled',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->placements()->attach($placement);

        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'status' => 'approved',
            'media_path' => 'marketing/test/missing-file.jpg',
            'destination_url' => 'https://example.com',
        ]);
        $creative->placements()->attach($placement);

        Artisan::call('advertising:sync-campaign-statuses');

        $this->assertSame('scheduled', $campaign->fresh()->status);
        $this->assertStringContainsString('1 blocked', Artisan::output());
    }

    public function test_expired_active_paused_and_scheduled_campaigns_complete_automatically(): void
    {
        $advertiser = Advertiser::factory()->create(['status' => 'active']);

        $campaigns = collect(['active', 'paused', 'scheduled'])->map(fn (string $status) => AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => $status,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subMinute(),
        ]));

        Artisan::call('advertising:sync-campaign-statuses');

        foreach ($campaigns as $campaign) {
            $this->assertSame('completed', $campaign->fresh()->status);
        }

        $this->assertDatabaseCount('admin_activities', 3);
    }
}
