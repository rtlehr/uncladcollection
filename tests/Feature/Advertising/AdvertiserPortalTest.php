<?php

namespace Tests\Feature\Advertising;

use App\Models\{Advertiser,AdvertiserMembership,AdvertisingCampaign,AdvertisingInvoice,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertiserPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_only_access_their_advertiser_records(): void
    {
        $user=User::factory()->create(['email_verified_at'=>now()]);
        $advertiser=Advertiser::factory()->create();
        AdvertiserMembership::factory()->create(['advertiser_id'=>$advertiser->id,'user_id'=>$user->id,'role'=>'owner','is_primary'=>true]);
        $campaign=AdvertisingCampaign::factory()->create(['advertiser_id'=>$advertiser->id]);
        $other=AdvertisingCampaign::factory()->create();

        $this->actingAs($user)->get('/advertiser')->assertOk()->assertInertia(fn($page)=>$page->component('Advertiser/Dashboard'));
        $this->actingAs($user)->get('/advertiser/campaigns/'.$campaign->id)->assertOk();
        $this->actingAs($user)->get('/advertiser/campaigns/'.$other->id)->assertNotFound();
    }

    public function test_billing_access_is_role_scoped(): void
    {
        $user=User::factory()->create(['email_verified_at'=>now()]);$advertiser=Advertiser::factory()->create();
        AdvertiserMembership::factory()->create(['advertiser_id'=>$advertiser->id,'user_id'=>$user->id,'role'=>'creative_contributor']);
        $this->actingAs($user)->get('/advertiser/invoices')->assertForbidden();
    }

    public function test_non_member_cannot_enter_portal(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at'=>now()]))->get('/advertiser')->assertForbidden();
    }
}
