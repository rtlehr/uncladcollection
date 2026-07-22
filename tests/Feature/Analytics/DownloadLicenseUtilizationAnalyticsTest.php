<?php

namespace Tests\Feature\Analytics;

use App\Models\Download;
use App\Models\Image;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DownloadLicenseUtilizationAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_download_and_license_data_and_can_export(): void
    {
        $admin=User::factory()->create(['email_verified_at'=>now()]);
        foreach(['view_admin','view_reports'] as $name){$p=Permission::query()->create(['name'=>$name,'label'=>str($name)->replace('_',' ')->title(),'description'=>$name]);$admin->permissions()->attach($p->id);}
        $buyer=User::factory()->create();
        $image=Image::query()->create([
            'title'=>'Download Analytics Image',
            'slug'=>'download-analytics-image',
            'is_active'=>true,
        ]);
        $type=LicenseType::factory()->create();
        $order=Order::query()->create(['user_id'=>$buyer->id,'status'=>Order::STATUS_PAID,'subtotal_cents'=>1500,'total_cents'=>1500,'currency'=>'USD','paid_at'=>now()]);
        $item=OrderItem::query()->create(['order_id'=>$order->id,'image_id'=>$image->id,'license_type_id'=>$type->id,'status'=>OrderItem::STATUS_ACTIVE,'quantity'=>1,'unit_price_cents'=>1500,'total_price_cents'=>1500,'image_title'=>$image->title,'license_name'=>$type->name]);
        $license=License::query()->create(['user_id'=>$buyer->id,'image_id'=>$image->id,'order_id'=>$order->id,'order_item_id'=>$item->id,'license_type_id'=>$type->id,'status'=>License::STATUS_ACTIVE,'starts_at'=>now(),'download_limit'=>5,'downloads_used'=>1,'license_name'=>$type->name]);
        Download::query()->create(['user_id'=>$buyer->id,'image_id'=>$image->id,'license_id'=>$license->id,'order_item_id'=>$item->id,'download_type'=>'high_res','downloaded_at'=>now()]);
        $this->actingAs($admin)->get('/admin/analytics/downloads?period=7_days')->assertOk()->assertInertia(fn($page)=>$page->component('Admin/Analytics/Downloads/Index')->where('report.summary.downloads',1)->where('report.licenses.0.license_key',$license->license_key));
        $this->actingAs($admin)->get('/admin/analytics/downloads/'.$license->id.'?period=7_days')->assertOk()->assertInertia(fn($page)=>$page->component('Admin/Analytics/Downloads/Show')->where('report.performance.downloads_used',1)->has('report.timeline',7));
        $this->actingAs($admin)->get('/admin/analytics/downloads/export?period=7_days')->assertOk()->assertDownload();
    }
}
