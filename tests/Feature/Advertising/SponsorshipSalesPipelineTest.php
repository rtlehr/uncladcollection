<?php

namespace Tests\Feature\Advertising;

use App\Models\AdPlacement;
use App\Models\Advertiser;
use App\Models\Permission;
use App\Models\SponsorshipLead;
use App\Models\SponsorshipProposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorshipSalesPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_proposal_converts_once_into_campaign_invoice_and_inventory(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        foreach ([
            'view_admin',
            'manage_sponsorship_proposals',
            'convert_sponsorship_proposals',
        ] as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                [
                    'label' => str($name)->headline(),
                    'description' => $name,
                    'group_name' => 'Advertising',
                ],
            );

            $user->permissions()->syncWithoutDetaching($permission);
        }

        $advertiser = Advertiser::factory()->create();

        $placement = AdPlacement::factory()->create([
            'max_active_campaigns' => 2,
        ]);

        $lead = SponsorshipLead::create([
            'uuid' => (string) str()->uuid(),
            'company_name' => 'Future Sponsor',
            'stage' => 'proposal',
            'estimated_value_cents' => 50000,
            'probability' => 70,
        ]);

        $proposal = SponsorshipProposal::create([
            'uuid' => (string) str()->uuid(),
            'proposal_number' => 'SP-TEST',
            'sponsorship_lead_id' => $lead->id,
            'advertiser_id' => $advertiser->id,
            'created_by' => $user->id,
            'title' => 'Launch Sponsor',
            'status' => 'accepted',
            'starts_on' => today()->addDay(),
            'ends_on' => today()->addMonth(),
            'currency' => 'USD',
            'subtotal_cents' => 50000,
            'total_cents' => 50000,
            'accepted_at' => now(),
        ]);

        $proposal->items()->create([
            'ad_placement_id' => $placement->id,
            'description' => 'Homepage placement',
            'billing_model' => 'sponsorship',
            'quantity' => 1,
            'unit_amount_cents' => 50000,
            'line_total_cents' => 50000,
        ]);

        $this
            ->actingAs($user)
            ->post('/admin/sponsorship-proposals/'.$proposal->id.'/convert')
            ->assertRedirect();

        $proposal->refresh();

        $this->assertNotNull($proposal->converted_campaign_id);
        $this->assertNotNull($proposal->converted_invoice_id);

        $this->assertSame(
            'sponsorship',
            $proposal->campaign->objective
        );

        $this->assertDatabaseCount('ad_inventory_reservations', 1);

        $this
            ->actingAs($user)
            ->post('/admin/sponsorship-proposals/'.$proposal->id.'/convert')
            ->assertSessionHasErrors('proposal');
    }
}