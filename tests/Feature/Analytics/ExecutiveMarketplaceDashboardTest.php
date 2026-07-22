<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Enums\AssetStatus;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveMarketplaceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_executive_dashboard_data(): void
    {
        CarbonImmutable::setTestNow('2026-07-22 12:00:00');
        $user = User::factory()->create();
        $user->permissions()->create(['name' => 'view_reports', 'label' => 'View reports']);
        $user->permissions()->create(['name' => 'view_admin', 'label' => 'View admin']);

        $asset = Asset::factory()->create(['title' => 'Executive Test Asset', 'status' => AssetStatus::Published, 'is_active' => true, 'published_at' => now()]);
        $licenseType = LicenseType::factory()->create(['slug' => 'executive-test']);
        AssetOffering::factory()->create(['asset_id' => $asset->id, 'license_type_id' => $licenseType->id, 'is_active' => true]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PAID,
            'subtotal_cents' => 2400,
            'total_cents' => 2400,
            'currency' => 'USD',
            'paid_at' => now(),
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'asset_id' => $asset->id,
            'license_type_id' => $licenseType->id,
            'status' => OrderItem::STATUS_ACTIVE,
            'quantity' => 2,
            'unit_price_cents' => 1200,
            'total_price_cents' => 2400,
            'image_title' => $asset->title,
            'asset_title' => $asset->title,
            'license_name' => $licenseType->name,
        ]);
        AnalyticsEvent::query()->create([
            'event_uuid' => (string) str()->uuid(),
            'event_name' => AnalyticsEventName::AssetViewed,
            'subject_type' => $asset->getMorphClass(),
            'subject_id' => $asset->id,
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)->get('/admin/analytics?period=7_days')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Analytics/Index')
                ->where('dashboard.summary.revenue_cents.value', 2400)
                ->has('dashboard.revenue_trend', 7)
                ->where('dashboard.top_assets.0.title', 'Executive Test Asset')
                ->where('dashboard.license_mix.0.revenue_cents', 2400)
                ->has('dashboard.conversion_funnel', 4)
            );
    }

    public function test_custom_period_requires_valid_dates(): void
    {
        $user = User::factory()->create();
        $user->permissions()->create(['name' => 'view_reports', 'label' => 'View reports']);
        $user->permissions()->create(['name' => 'view_admin', 'label' => 'View admin']);

        $this->actingAs($user)->get('/admin/analytics?period=custom&start_date=2026-07-22&end_date=2026-07-01')
            ->assertSessionHasErrors('end_date');
    }
}
