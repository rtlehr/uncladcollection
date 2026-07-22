<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerConversionAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_customer_conversion_data_and_can_export(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        $admin->permissions()->attach(Permission::query()->create(['name'=>'view_admin','label'=>'View admin']));
        $admin->permissions()->attach(Permission::query()->create(['name'=>'view_reports','label'=>'View reports']));
        $buyer=User::factory()->create();
        Order::query()->create(['user_id'=>$buyer->id,'status'=>Order::STATUS_PAID,'subtotal_cents'=>2500,'total_cents'=>2500,'currency'=>'USD','paid_at'=>now()]);
        AnalyticsEvent::query()->create(['event_uuid'=>(string)str()->uuid(),'event_name'=>AnalyticsEventName::AssetViewed,'user_id'=>$buyer->id,'occurred_at'=>now()]);

        $this->actingAs($admin)->get('/admin/analytics/customers?period=7_days')->assertOk()->assertInertia(fn($page)=>$page->component('Admin/Analytics/Customers/Index')->where('report.summary.buyers',1)->where('report.summary.new_customers',1)->where('report.summary.revenue_cents',2500)->where('report.customers.0.email',$buyer->email));
        $this->actingAs($admin)->get('/admin/analytics/customers/'.$buyer->id.'?period=7_days')->assertOk()->assertInertia(fn($page)=>$page->component('Admin/Analytics/Customers/Show')->where('report.performance.period_revenue_cents',2500));
        $this->actingAs($admin)->get('/admin/analytics/customers/export?period=7_days')->assertOk()->assertDownload();
    }
}
