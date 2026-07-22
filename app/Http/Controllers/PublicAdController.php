<?php

namespace App\Http\Controllers;

use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Models\AdCreative;
use App\Services\PublicAdDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAdController extends Controller
{
    public function show(string $placement, PublicAdDeliveryService $delivery): JsonResponse
    {
        $ad = $delivery->select($placement);
        return $ad ? response()->json($ad) : response()->json(null, 204);
    }

    public function impression(Request $request, AdCreative $creative, AnalyticsTracker $tracker): JsonResponse
    {
        $data = $request->validate(['placement_code' => 'required|string|max:100']);
        abort_unless($creative->status === 'approved', 404);

        $tracker->record(
            AnalyticsEventName::AdvertisingImpression,
            $creative,
            $request->user(),
            ['placement_code' => $data['placement_code'], 'campaign_id' => $creative->advertising_campaign_id],
            source: 'public_ad',
            channel: 'advertising',
            deduplicationKey: 'ad-impression:'.$creative->id.':'.$data['placement_code'],
        );

        return response()->json(['recorded' => true]);
    }

    public function click(Request $request, AdCreative $creative, AnalyticsTracker $tracker): JsonResponse
    {
        $data = $request->validate(['placement_code' => 'required|string|max:100']);
        abort_unless($creative->status === 'approved', 404);

        $tracker->record(
            AnalyticsEventName::AdvertisingClicked,
            $creative,
            $request->user(),
            ['placement_code' => $data['placement_code'], 'campaign_id' => $creative->advertising_campaign_id],
            source: 'public_ad',
            channel: 'advertising',
            deduplicationKey: 'ad-click:'.$creative->id.':'.$data['placement_code'].':'.now()->format('YmdHi'),
        );

        return response()->json(['recorded' => true]);
    }
}
