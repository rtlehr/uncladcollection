<?php

namespace Tests\Feature\Advertising;

use App\Models\{Advertiser, AdvertiserMembership, SponsorshipProposal, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorshipProposalPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_advertiser_owner_can_accept_sent_proposal_with_audit_record(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $advertiser = Advertiser::factory()->create();
        AdvertiserMembership::create(['advertiser_id'=>$advertiser->id,'user_id'=>$user->id,'role'=>'owner','is_primary'=>true,'is_active'=>true,'accepted_at'=>now()]);
        $proposal = SponsorshipProposal::create(['uuid'=>(string)str()->uuid(),'proposal_number'=>'SP-PORTAL','advertiser_id'=>$advertiser->id,'created_by'=>$user->id,'title'=>'Portal Proposal','status'=>'sent','starts_on'=>today()->addDay(),'ends_on'=>today()->addMonth(),'expires_on'=>today()->addWeek(),'currency'=>'USD','subtotal_cents'=>50000,'total_cents'=>50000,'sent_at'=>now()]);

        $this->actingAs($user)->post("/advertiser/proposals/{$proposal->id}/accept", [
            'signer_name'=>'Alex Sponsor','signer_title'=>'President','signer_email'=>$user->email,
            'signer_company'=>$advertiser->name,'terms_acknowledged'=>true,
        ])->assertRedirect();

        $this->assertDatabaseHas('sponsorship_proposals',['id'=>$proposal->id,'status'=>'accepted']);
        $this->assertDatabaseHas('sponsorship_proposal_acceptances',['sponsorship_proposal_id'=>$proposal->id,'signer_name'=>'Alex Sponsor','terms_acknowledged'=>1]);
        $this->assertDatabaseHas('sponsorship_proposal_status_histories',['sponsorship_proposal_id'=>$proposal->id,'from_status'=>'sent','to_status'=>'accepted','source'=>'advertiser_portal']);
    }

    public function test_other_advertiser_cannot_view_or_respond_to_proposal(): void
    {
        $user=User::factory()->create(['email_verified_at'=>now()]);
        $memberAdvertiser=Advertiser::factory()->create();
        $other=Advertiser::factory()->create();
        AdvertiserMembership::create(['advertiser_id'=>$memberAdvertiser->id,'user_id'=>$user->id,'role'=>'owner','is_active'=>true,'accepted_at'=>now()]);
        $proposal=SponsorshipProposal::create(['uuid'=>(string)str()->uuid(),'proposal_number'=>'SP-OTHER','advertiser_id'=>$other->id,'created_by'=>$user->id,'title'=>'Other Proposal','status'=>'sent','starts_on'=>today()->addDay(),'ends_on'=>today()->addMonth(),'expires_on'=>today()->addWeek(),'currency'=>'USD','subtotal_cents'=>10000,'total_cents'=>10000]);
        $this->actingAs($user)->get("/advertiser/proposals/{$proposal->id}")->assertNotFound();
    }

    public function test_report_viewer_cannot_accept_proposal_and_expired_proposal_cannot_be_accepted(): void
    {
        $user=User::factory()->create(['email_verified_at'=>now()]);
        $advertiser=Advertiser::factory()->create();
        AdvertiserMembership::create(['advertiser_id'=>$advertiser->id,'user_id'=>$user->id,'role'=>'report_viewer','is_active'=>true,'accepted_at'=>now()]);
        $proposal=SponsorshipProposal::create(['uuid'=>(string)str()->uuid(),'proposal_number'=>'SP-EXPIRED','advertiser_id'=>$advertiser->id,'created_by'=>$user->id,'title'=>'Expired Proposal','status'=>'sent','starts_on'=>today()->addDay(),'ends_on'=>today()->addMonth(),'expires_on'=>today()->subDay(),'currency'=>'USD','subtotal_cents'=>10000,'total_cents'=>10000]);
        $this->actingAs($user)->post("/advertiser/proposals/{$proposal->id}/accept", ['signer_name'=>'No','signer_email'=>$user->email,'terms_acknowledged'=>true])->assertForbidden();
    }
}
