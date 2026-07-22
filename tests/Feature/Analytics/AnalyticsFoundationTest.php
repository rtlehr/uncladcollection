<?php

namespace Tests\Feature\Analytics;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\AnalyticsTracker;
use App\Analytics\MarketplaceMetricsService;
use App\Enums\AnalyticsEventName;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_records_a_normalized_marketplace_event(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->create();

        $event = app(AnalyticsTracker::class)->record(
            AnalyticsEventName::AssetViewed,
            subject: $asset,
            user: $user,
            dimensions: ['surface' => 'public_asset_show'],
            source: 'direct',
        );

        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
            'event_name' => 'asset_viewed',
            'subject_type' => $asset->getMorphClass(),
            'subject_id' => $asset->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_metrics_service_returns_period_and_comparison_values(): void
    {
        CarbonImmutable::setTestNow('2026-07-22 12:00:00');

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'status' => Order::STATUS_PAID,
            'subtotal_cents' => 2500,
            'total_cents' => 2500,
            'currency' => 'USD',
            'paid_at' => now(),
        ]);

        $period = new AnalyticsPeriod(CarbonImmutable::now()->startOfDay(), CarbonImmutable::now()->endOfDay());
        $metrics = app(MarketplaceMetricsService::class)->summary($period);

        $this->assertSame(2500, $metrics['revenue_cents']['value']);
        $this->assertSame(1, $metrics['paid_orders']['value']);
        $this->assertSame(2500, $metrics['average_order_value_cents']['value']);
    }
}
