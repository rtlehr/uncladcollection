<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Models\AdvertisingCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends PortalController
{
    public function index(Request $request)
    {
        return Inertia::render('Advertiser/Campaigns/Index', [
            'advertiser' => $this->advertiser($request),
            'membership' => $this->membership($request),
            'campaigns' => $this->advertiser($request)->campaigns()->withCount('creatives')->with('placements')->latest()->get(),
        ]);
    }

    public function show(Request $request, AdvertisingCampaign $campaign)
    {
        abort_unless($campaign->advertiser_id === $this->advertiser($request)->id, 404);
        return Inertia::render('Advertiser/Campaigns/Show', [
            'advertiser' => $this->advertiser($request),
            'membership' => $this->membership($request),
            'campaign' => $campaign->load(['placements', 'creatives.placement']),
        ]);
    }
}
