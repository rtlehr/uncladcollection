<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\MarketingCampaign;
use App\Models\Order;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCampaignPerformanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_campaign_performance_and_can_export(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->permissions()->attach(Permission::query()->create(['name' => 'view_admin', 'label' => 'View admin']));
        $admin->permissions()->attach(Permission::query()->create(['name' => 'view_reports', 'label' => 'View reports']));
        $buyer = User::factory()->create();
        $campaign = MarketingCampaign::query()->create([
            'uuid' => (string) str()->uuid(), 'name' => 'Summer Campaign', 'media_type' => 'image', 'media_path' => 'marketing/campaigns/tests/summer-campaign.jpg',
            'overlay_opacity' => 30, 'media_position' => 'center', 'hero_height' => 'large', 'text_alignment' => 'left',
            'autoplay_first_visit' => false, 'autoplay_mobile' => false, 'loop_video' => false, 'show_search' => true,
            'is_active' => true, 'sort_order' => 1,
        ]);
        foreach ([AnalyticsEventName::CampaignViewed, AnalyticsEventName::CampaignClicked] as $event) {
            AnalyticsEvent::query()->create(['event_uuid' => (string) str()->uuid(), 'event_name' => $event, 'subject_type' => $campaign->getMorphClass(), 'subject_id' => $campaign->id, 'user_id' => $buyer->id, 'session_id' => 'campaign-session', 'dimensions' => $event === AnalyticsEventName::CampaignClicked ? ['button' => 'primary'] : null, 'occurred_at' => now()]);
        }
        Order::query()->create(['user_id' => $buyer->id, 'status' => Order::STATUS_PAID, 'subtotal_cents' => 3200, 'total_cents' => 3200, 'currency' => 'USD', 'paid_at' => now()]);

        $this->actingAs($admin)->get('/admin/analytics/campaigns?period=7_days')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Campaigns/Index')->where('report.summary.impressions', 1)->where('report.summary.clicks', 1)->where('report.summary.influenced_revenue_cents', 3200)->where('report.campaigns.0.name', 'Summer Campaign'));
        $this->actingAs($admin)->get('/admin/analytics/campaigns/'.$campaign->id.'?period=7_days')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Analytics/Campaigns/Show')->where('report.performance.influenced_revenue_cents', 3200)->has('report.timeline', 7));
        $this->actingAs($admin)->get('/admin/analytics/campaigns/export?period=7_days')->assertOk()->assertDownload();
    }

    public function test_public_tracking_endpoints_record_campaign_events(): void
    {
        $campaign = MarketingCampaign::query()->create(['uuid' => (string) str()->uuid(), 'name' => 'Tracked Campaign', 'media_type' => 'image', 'media_path' => 'marketing/campaigns/tests/tracked-campaign.jpg', 'overlay_opacity' => 30, 'media_position' => 'center', 'hero_height' => 'large', 'text_alignment' => 'left', 'autoplay_first_visit' => false, 'autoplay_mobile' => false, 'loop_video' => false, 'show_search' => true, 'is_active' => true, 'sort_order' => 1]);
        $this->postJson('/marketing-campaigns/'.$campaign->id.'/impression')->assertOk();
        $this->postJson('/marketing-campaigns/'.$campaign->id.'/click', ['button' => 'primary'])->assertOk();
        $this->assertDatabaseCount('analytics_events', 2);
    }
}
