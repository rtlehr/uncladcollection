<?php

namespace App\Advertising;

use App\Models\Advertiser;
use App\Models\AdvertisingCampaign;
use App\Models\SponsorshipLead;
use Illuminate\Http\Request;

class AdvertisingWorkflowContextService
{
    public function fromRequest(Request $request): array
    {
        $campaign = $request->filled('campaign_id')
            ? AdvertisingCampaign::with('advertiser')->find($request->integer('campaign_id'))
            : null;

        $lead = $request->filled('lead_id')
            ? SponsorshipLead::with('advertiser')->find($request->integer('lead_id'))
            : null;

        $advertiser = $campaign?->advertiser
            ?? $lead?->advertiser
            ?? ($request->filled('advertiser_id') ? Advertiser::find($request->integer('advertiser_id')) : null);

        return $this->payload($advertiser, $lead, $campaign);
    }

    public function payload(?Advertiser $advertiser, ?SponsorshipLead $lead = null, ?AdvertisingCampaign $campaign = null): array
    {
        return [
            'active' => (bool) $advertiser,
            'advertiser' => $advertiser ? [
                'id' => $advertiser->id,
                'name' => $advertiser->name,
                'status' => $advertiser->status,
                'contact_name' => $advertiser->contact_name,
                'contact_email' => $advertiser->contact_email,
                'contact_phone' => $advertiser->contact_phone,
                'billing_email' => $advertiser->billing_email,
                'workspace_href' => "/admin/advertisers/{$advertiser->id}",
            ] : null,
            'lead' => $lead ? [
                'id' => $lead->id,
                'company_name' => $lead->company_name,
                'stage' => $lead->stage,
            ] : null,
            'campaign' => $campaign ? [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'public_code' => $campaign->public_code,
                'status' => $campaign->status,
            ] : null,
        ];
    }
}
