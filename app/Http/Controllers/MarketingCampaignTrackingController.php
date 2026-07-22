<?php

namespace App\Http\Controllers;

use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Models\MarketingCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketingCampaignTrackingController extends Controller
{
    public function impression(Request $request, MarketingCampaign $marketingCampaign, AnalyticsTracker $tracker): JsonResponse
    {
        $tracker->record(
            AnalyticsEventName::CampaignViewed,
            $marketingCampaign,
            $request->user(),
            ['placement' => 'home_hero'],
            source: 'marketing_campaign',
            channel: 'onsite',
        )->update(['session_id' => $request->session()->getId()]);

        return response()->json(['recorded' => true]);
    }

    public function click(Request $request, MarketingCampaign $marketingCampaign, AnalyticsTracker $tracker): JsonResponse
    {
        $validated = $request->validate(['button' => ['required', 'in:primary,secondary']]);
        $tracker->record(
            AnalyticsEventName::CampaignClicked,
            $marketingCampaign,
            $request->user(),
            ['placement' => 'home_hero', 'button' => $validated['button']],
            source: 'marketing_campaign',
            channel: 'onsite',
        )->update(['session_id' => $request->session()->getId()]);

        return response()->json(['recorded' => true]);
    }
}
