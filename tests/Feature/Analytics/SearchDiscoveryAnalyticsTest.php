<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchDiscoveryAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_search_discovery_data_and_can_export(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        foreach (['view_admin', 'view_reports'] as $name) { $permission = Permission::query()->firstOrCreate(['name' => $name], ['label' => str($name)->replace('_', ' ')->title()->toString(), 'description' => $name]); $admin->permissions()->syncWithoutDetaching([$permission->id]); }
        $buyer = User::factory()->create();
        AnalyticsEvent::query()->create(['event_uuid' => (string) str()->uuid(), 'event_name' => AnalyticsEventName::SearchPerformed, 'user_id' => $buyer->id, 'session_id' => 'search-session', 'dimensions' => ['term' => 'sunset', 'result_count' => 2], 'occurred_at' => now()]);
        Order::query()->create(['user_id' => $buyer->id, 'status' => Order::STATUS_PAID, 'subtotal_cents' => 1900, 'total_cents' => 1900, 'currency' => 'USD', 'paid_at' => now()]);

        $this->actingAs($admin)->get('/admin/analytics/search?period=7_days')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Analytics/Search/Index')->where('report.summary.searches', 1)->where('report.summary.influenced_revenue_cents', 1900)->where('report.terms.0.term', 'sunset'));
        $this->actingAs($admin)->get('/admin/analytics/search/sunset?period=7_days')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Analytics/Search/Show')->where('report.performance.influenced_revenue_cents', 1900)->has('report.timeline', 7));
        $this->actingAs($admin)->get('/admin/analytics/search/export?period=7_days')->assertOk()->assertDownload();
    }
}
