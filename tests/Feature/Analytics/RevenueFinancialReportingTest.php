<?php

namespace Tests\Feature\Analytics;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\FinancialReportingService;
use App\Enums\FinancialTransactionStatus;
use App\Enums\FinancialTransactionType;
use App\Enums\OrderFulfillmentStatus;
use App\Models\Asset;
use App\Models\FinancialTransaction;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueFinancialReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_calculates_collected_refunded_and_net_revenue(): void
    {
        CarbonImmutable::setTestNow('2026-07-22 12:00:00');
        $order = $this->paidOrder();
        FinancialTransaction::query()->create([
            'order_id' => $order->id,
            'type' => FinancialTransactionType::Refund,
            'status' => FinancialTransactionStatus::Succeeded,
            'amount_cents' => 500,
            'currency' => 'USD',
            'provider' => 'stripe',
            'provider_reference' => 're_test_001',
            'occurred_at' => now(),
        ]);

        $period = new AnalyticsPeriod(CarbonImmutable::today()->startOfDay(), CarbonImmutable::today()->endOfDay());
        $report = app(FinancialReportingService::class)->report($period);

        $this->assertSame(2400, $report['summary']['collected_revenue_cents']);
        $this->assertSame(500, $report['summary']['refunds_cents']);
        $this->assertSame(1900, $report['summary']['net_revenue_cents']);
        $this->assertSame(2, $report['summary']['units_sold']);
    }

    public function test_report_viewer_can_open_and_export_financial_report(): void
    {
        CarbonImmutable::setTestNow('2026-07-22 12:00:00');
        $user = User::factory()->create();
        $user->permissions()->create(['name' => 'view_reports', 'label' => 'View reports']);
        $user->permissions()->create(['name' => 'view_admin', 'label' => 'View admin']);
        $this->paidOrder($user);

        $this->actingAs($user)->get('/admin/analytics/financial?period=7_days')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Analytics/Financial')
                ->where('report.summary.collected_revenue_cents', 2400)
                ->where('report.summary.net_revenue_cents', 2400)
                ->has('report.orders', 1)
            );

        $this->actingAs($user)->get('/admin/analytics/financial/export?period=7_days')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    private function paidOrder(?User $user = null): Order
    {
        $user ??= User::factory()->create();
        $asset = Asset::factory()->create(['title' => 'Financial Test Asset']);
        $license = LicenseType::factory()->create(['slug' => 'financial-test-'.str()->lower(str()->random(8))]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PAID,
            'fulfillment_status' => OrderFulfillmentStatus::New,
            'subtotal_cents' => 2600,
            'discount_cents' => 200,
            'tax_cents' => 0,
            'total_cents' => 2400,
            'currency' => 'USD',
            'payment_provider' => 'stripe',
            'payment_reference' => 'pi_test_001',
            'paid_at' => now(),
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'asset_id' => $asset->id,
            'license_type_id' => $license->id,
            'status' => OrderItem::STATUS_ACTIVE,
            'quantity' => 2,
            'unit_price_cents' => 1200,
            'total_price_cents' => 2400,
            'image_title' => $asset->title,
            'asset_title' => $asset->title,
            'license_name' => $license->name,
        ]);
        return $order;
    }
}
