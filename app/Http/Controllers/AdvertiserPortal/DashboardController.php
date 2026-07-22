<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Models\AnalyticsEvent;
use App\Enums\AnalyticsEventName;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends PortalController
{
    public function __invoke(Request $request)
    {
        $advertiser = $this->advertiser($request);
        $campaignIds = $advertiser->campaigns()->pluck('id');
        $campaigns = $advertiser->campaigns()->withCount('creatives')->latest()->limit(6)->get();
        $invoices = $advertiser->invoices()->latest()->limit(5)->get();

        $events = AnalyticsEvent::query()
            ->whereIn('subject_id', $campaignIds)
            ->where('subject_type', (new \App\Models\AdvertisingCampaign)->getMorphClass())
            ->whereIn('event_name', [AnalyticsEventName::AdvertisingImpression, AnalyticsEventName::AdvertisingClicked])
            ->selectRaw('event_name, COUNT(*) aggregate')
            ->groupBy('event_name')->get();

        $counts = $events->mapWithKeys(fn ($row) => [($row->event_name instanceof \BackedEnum ? $row->event_name->value : $row->event_name) => (int) $row->aggregate]);

        return Inertia::render('Advertiser/Dashboard', [
            'advertiser' => $advertiser,
            'membership' => $this->membership($request),
            'summary' => [
                'campaigns' => $advertiser->campaigns()->count(),
                'active_campaigns' => $advertiser->campaigns()->where('status', 'active')->count(),
                'open_balance_cents' => $advertiser->invoices()->whereNotIn('status', ['paid','void','refunded'])->sum('balance_cents'),
                'impressions' => $counts->get(AnalyticsEventName::AdvertisingImpression->value, 0),
                'clicks' => $counts->get(AnalyticsEventName::AdvertisingClicked->value, 0),
            ],
            'campaigns' => $campaigns,
            'invoices' => $invoices,
        ]);
    }
}
