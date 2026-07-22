<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Enums\AssetStatus;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetPerformanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_asset_performance_and_can_export(): void
    {
        CarbonImmutable::setTestNow('2026-07-22 12:00:00');
        $user = User::factory()->create();
        $user->permissions()->create(['name' => 'view_reports', 'label' => 'View reports']);
        $user->permissions()->create(['name' => 'view_admin', 'label' => 'View admin']);
        $asset = Asset::factory()->create(['title' => 'Performance Asset', 'status' => AssetStatus::Published, 'is_active' => true, 'published_at' => now()]);
        $license = LicenseType::factory()->create(['slug' => 'performance-license']);
        foreach ([AnalyticsEventName::AssetViewed, AnalyticsEventName::AssetViewed, AnalyticsEventName::AssetAddedToCart] as $event) {
            AnalyticsEvent::query()->create(['event_uuid' => (string) str()->uuid(), 'event_name' => $event, 'subject_type' => $asset->getMorphClass(), 'subject_id' => $asset->id, 'occurred_at' => now()]);
        }
        $order = Order::query()->create(['user_id' => $user->id, 'status' => Order::STATUS_PAID, 'subtotal_cents' => 1200, 'total_cents' => 1200, 'currency' => 'USD', 'paid_at' => now()]);
        OrderItem::query()->create(['order_id' => $order->id, 'asset_id' => $asset->id, 'license_type_id' => $license->id, 'status' => OrderItem::STATUS_ACTIVE, 'quantity' => 1, 'unit_price_cents' => 1200, 'total_price_cents' => 1200, 'image_title' => $asset->title, 'asset_title' => $asset->title, 'license_name' => $license->name]);

        $this->actingAs($user)
        ->get('/admin/analytics/assets?period=7_days')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Assets/Index')
            ->where('report.summary.views', 2)
            ->where('report.summary.units_sold', 1)
            ->where('report.assets.0.title', 'Performance Asset')
            ->where('report.assets.0.view_to_purchase_percent', 50)
        );
    $this->actingAs($user)->get('/admin/analytics/assets/'.$asset->id.'?period=7_days')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Analytics/Assets/Show')->where('report.performance.revenue_cents', 1200)->has('report.timeline', 7));
        $this->actingAs($user)->get('/admin/analytics/assets/export?period=7_days')->assertOk()->assertDownload();
    }
}
