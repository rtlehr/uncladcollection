<?php

namespace Tests\Feature\Analytics;

use App\Enums\OrderFulfillmentStatus;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceOperationsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_operations_data_and_can_export(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        foreach (['view_admin', 'view_reports'] as $name) {
            $permission = Permission::query()->create(['name' => $name, 'label' => str($name)->replace('_', ' ')->title(), 'description' => $name]);
            $admin->permissions()->attach($permission->id);
        }
        $buyer = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $buyer->id,
            'status' => Order::STATUS_PAID,
            'fulfillment_status' => OrderFulfillmentStatus::Fulfilled,
            'subtotal_cents' => 3200,
            'total_cents' => 3200,
            'currency' => 'USD',
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'paid_at' => now()->subHours(4),
            'fulfilled_at' => now(),
        ]);
        OrderFulfillmentEvent::query()->create(['order_id' => $order->id, 'status' => OrderFulfillmentStatus::Fulfilled->value, 'created_at' => now()]);

        $this->actingAs($admin)->get('/admin/analytics/operations?period=7_days')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Operations/Index')
            ->where('report.summary.orders', 1)
            ->where('report.summary.paid_orders', 1)
            ->where('report.summary.revenue_cents', 3200)
            ->where('report.orders.0.order_number', $order->order_number));
        $this->actingAs($admin)->get('/admin/analytics/operations/'.$order->id.'?period=7_days')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Operations/Show')
            ->where('report.order.order_number', $order->order_number)
            ->has('report.fulfillment_events', 1));
        $this->actingAs($admin)->get('/admin/analytics/operations/export?period=7_days')->assertOk()->assertDownload();
    }
}
