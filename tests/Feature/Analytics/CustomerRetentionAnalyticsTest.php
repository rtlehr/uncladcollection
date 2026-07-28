<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRetentionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_can_open_retention_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->permissions()->create(['name' => 'view_admin', 'label' => 'View admin']);
        $admin->permissions()->create(['name' => 'view_reports', 'label' => 'View reports']);
        $buyer = User::factory()->create();

        Order::query()->create(['user_id' => $buyer->id, 'status' => Order::STATUS_PAID, 'subtotal_cents' => 1000, 'discount_cents' => 0, 'tax_cents' => 0, 'total_cents' => 1000, 'currency' => 'USD', 'paid_at' => now()->subDays(40)]);
        Order::query()->create(['user_id' => $buyer->id, 'status' => Order::STATUS_PAID, 'subtotal_cents' => 1500, 'discount_cents' => 0, 'tax_cents' => 0, 'total_cents' => 1500, 'currency' => 'USD', 'paid_at' => now()]);
        AnalyticsEvent::query()->create(['event_uuid' => (string) str()->uuid(), 'event_name' => AnalyticsEventName::AccountDashboardViewed, 'user_id' => $buyer->id, 'occurred_at' => now()]);

        $this->actingAs($admin)->get(route('admin.analytics.retention'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Analytics/Retention')->where('report.summary.repeat_buyers', 1)->where('report.summary.account_visits', 1));
    }
}
