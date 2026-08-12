<?php

namespace Tests\Feature\Advertising;

use App\Models\{AdCreative, AdPlacement, Advertiser, AdvertisingCampaign, Permission, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisingCampaignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_proposal_objective_does_not_block_campaign_schedule_edit(): void
    {
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create();
        $placement = AdPlacement::factory()->create();
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'objective' => 'Sponsorship proposal SP-LEGACY',
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(10),
        ]);
        $campaign->placements()->attach($placement);

        $this->actingAs($user)->put('/admin/ad-campaigns/'.$campaign->id, [
            'advertiser_id' => $advertiser->id,
            'name' => $campaign->name,
            'objective' => 'Sponsorship proposal SP-LEGACY',
            'pricing_model' => 'flat',
            'budget_cents' => 100000,
            'contract_value_cents' => 100000,
            'starts_at' => now()->startOfDay()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(10)->endOfDay()->format('Y-m-d\TH:i'),
            'placement_ids' => [$placement->id],
        ])->assertRedirect('/admin/ad-campaigns/'.$campaign->id);

        $campaign->refresh();
        $this->assertSame('sponsorship', $campaign->objective);
        $this->assertTrue($campaign->starts_at->isToday());
    }

    public function test_launch_ready_approved_campaign_can_be_activated(): void
    {
        Storage::fake('public');
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create(['is_active' => true]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'approved',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->placements()->attach($placement);
        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'status' => 'approved',
            'media_path' => 'marketing/test/creative.jpg',
            'destination_url' => 'https://example.com',
        ]);
        $creative->placements()->attach($placement);
        Storage::disk('public')->put('marketing/test/creative.jpg', 'image-bytes');

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/launch')
            ->assertRedirect();

        $this->assertSame('active', $campaign->fresh()->status);
    }

    public function test_launch_ready_campaign_with_future_start_is_scheduled(): void
    {
        Storage::fake('public');
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create(['is_active' => true]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'approved',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->placements()->attach($placement);
        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'status' => 'approved',
            'media_path' => 'marketing/test/future.jpg',
            'destination_url' => 'https://example.com',
        ]);
        $creative->placements()->attach($placement);
        Storage::disk('public')->put('marketing/test/future.jpg', 'image-bytes');

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/launch')
            ->assertRedirect();

        $this->assertSame('scheduled', $campaign->fresh()->status);
    }


    public function test_campaign_cannot_launch_when_an_assigned_placement_has_no_approved_creative(): void
    {
        Storage::fake('public');
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $coveredPlacement = AdPlacement::factory()->create(['is_active' => true, 'width' => 760, 'height' => 240]);
        $uncoveredPlacement = AdPlacement::factory()->create(['is_active' => true, 'width' => 300, 'height' => 250]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'approved',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->placements()->attach([$coveredPlacement->id, $uncoveredPlacement->id]);

        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'ad_placement_id' => $coveredPlacement->id,
            'status' => 'approved',
            'media_path' => 'marketing/test/covered.jpg',
            'destination_url' => 'https://example.com',
            'width' => 760,
            'height' => 240,
        ]);
        $creative->placements()->attach($coveredPlacement);
        Storage::disk('public')->put('marketing/test/covered.jpg', 'image-bytes');

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/launch')
            ->assertSessionHasErrors('campaign');

        $this->assertSame('approved', $campaign->fresh()->status);
    }

    public function test_campaign_rotation_weight_can_be_updated_per_placement_without_losing_allocated_budget(): void
    {
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create();
        $placement = AdPlacement::factory()->create();
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'objective' => 'awareness',
            'pricing_model' => 'flat',
            'budget_cents' => 100000,
            'contract_value_cents' => 100000,
        ]);
        $campaign->placements()->attach($placement->id, [
            'priority' => 50,
            'allocated_budget_cents' => 25000,
        ]);

        $this->actingAs($user)->put('/admin/ad-campaigns/'.$campaign->id, [
            'advertiser_id' => $advertiser->id,
            'name' => $campaign->name,
            'objective' => 'awareness',
            'pricing_model' => 'flat',
            'budget_cents' => 100000,
            'contract_value_cents' => 100000,
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'placement_ids' => [$placement->id],
            'placement_priorities' => [$placement->id => 80],
        ])->assertRedirect('/admin/ad-campaigns/'.$campaign->id);

        $pivot = $campaign->fresh()->placements()->whereKey($placement->id)->firstOrFail()->pivot;

        $this->assertSame(80, (int) $pivot->priority);
        $this->assertSame(25000, (int) $pivot->allocated_budget_cents);
    }


    public function test_active_campaign_can_be_paused_resumed_and_completed_with_history(): void
    {
        Storage::fake('public');
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $placement = AdPlacement::factory()->create(['is_active' => true]);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ]);
        $campaign->placements()->attach($placement);
        $creative = AdCreative::factory()->create([
            'advertising_campaign_id' => $campaign->id,
            'status' => 'approved',
            'media_path' => 'marketing/test/lifecycle.jpg',
            'destination_url' => 'https://example.com',
        ]);
        $creative->placements()->attach($placement);
        Storage::disk('public')->put('marketing/test/lifecycle.jpg', 'image-bytes');

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/pause')
            ->assertRedirect();

        $this->assertSame('paused', $campaign->fresh()->status);

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/resume')
            ->assertRedirect();

        $this->assertSame('active', $campaign->fresh()->status);

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/complete')
            ->assertRedirect();

        $this->assertSame('completed', $campaign->fresh()->status);
        $this->assertDatabaseCount('admin_activities', 3);
        $this->assertDatabaseHas('admin_activities', [
            'subject_type' => AdvertisingCampaign::class,
            'subject_id' => $campaign->id,
            'field_name' => 'status',
            'new_value' => 'completed',
        ]);
    }

    public function test_paused_campaign_with_expired_end_date_cannot_resume(): void
    {
        $user = $this->campaignAdmin();
        $advertiser = Advertiser::factory()->create(['status' => 'active']);
        $campaign = AdvertisingCampaign::factory()->create([
            'advertiser_id' => $advertiser->id,
            'status' => 'paused',
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->post('/admin/ad-campaigns/'.$campaign->id.'/resume')
            ->assertSessionHasErrors('campaign');

        $this->assertSame('paused', $campaign->fresh()->status);
    }

    private function campaignAdmin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        foreach (['view_admin', 'manage_ad_campaigns', 'approve_ad_campaigns'] as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['label' => str($name)->headline(), 'description' => $name, 'group_name' => 'Advertising'],
            );
            $user->permissions()->syncWithoutDetaching($permission);
        }

        return $user;
    }
}
