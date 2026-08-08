<?php

namespace App\Advertising;

use App\Enums\AnalyticsEventName;
use App\Models\AdCreative;
use App\Models\AdPlacement;
use App\Models\AdvertisingCampaign;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Collection;

class AdvertisingRotationStatusService
{
    /**
     * Build a placement-by-placement view of intended weight versus actual
     * recorded impressions for the last 30 days.
     */
    public function forCampaign(AdvertisingCampaign $campaign, int $days = 30): array
    {
        $campaign->loadMissing('placements');
        $since = now()->subDays($days);

        return $campaign->placements->map(function (AdPlacement $placement) use ($campaign, $days, $since) {
            $campaigns = AdvertisingCampaign::query()
                ->with(['advertiser:id,name', 'placements' => fn ($query) => $query->whereKey($placement->id), 'creatives:id,advertising_campaign_id'])
                ->whereHas('placements', fn ($query) => $query->whereKey($placement->id))
                ->where(function ($query) use ($campaign) {
                    $query->current()->orWhere('id', $campaign->id);
                })
                ->whereHas('creatives', function ($query) use ($placement) {
                    $query->where('status', 'approved')
                        ->whereHas('placements', fn ($placementQuery) => $placementQuery->whereKey($placement->id));
                })
                ->orderBy('id')
                ->get();

            // Always include the campaign being viewed, even before it goes live,
            // so admins can see its intended weight before impressions exist.
            if (! $campaigns->contains('id', $campaign->id)) {
                $campaigns->push($campaign->loadMissing('advertiser'));
            }

            $campaignIds = $campaigns->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
            $creativeIds = $campaigns
                ->flatMap(fn (AdvertisingCampaign $item) => $item->creatives->pluck('id'))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $events = AnalyticsEvent::query()
                ->where('event_name', AnalyticsEventName::AdvertisingImpression)
                ->where('channel', 'advertising')
                ->where('subject_type', (new AdCreative())->getMorphClass())
                ->when($creativeIds->isNotEmpty(), fn ($query) => $query->whereIn('subject_id', $creativeIds))
                ->when($creativeIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
                ->where('occurred_at', '>=', $since)
                ->get(['dimensions'])
                ->filter(function (AnalyticsEvent $event) use ($campaignIds, $placement) {
                    $dimensions = $event->dimensions ?? [];

                    return (string) ($dimensions['placement_code'] ?? '') === (string) $placement->code
                        && $campaignIds->contains((int) ($dimensions['campaign_id'] ?? 0));
                });

            $impressionsByCampaign = $events
                ->countBy(fn (AnalyticsEvent $event) => (int) (($event->dimensions ?? [])['campaign_id'] ?? 0));

            $totalImpressions = (int) $impressionsByCampaign->sum();
            $totalWeight = max(1, (int) $campaigns->sum(function (AdvertisingCampaign $item) use ($placement) {
                return $this->weightFor($item, $placement);
            }));

            $rows = $campaigns->map(function (AdvertisingCampaign $item) use ($placement, $campaign, $impressionsByCampaign, $totalImpressions, $totalWeight) {
                $weight = $this->weightFor($item, $placement);
                $impressions = (int) ($impressionsByCampaign[$item->id] ?? 0);

                return [
                    'campaign_id' => $item->id,
                    'campaign_name' => $item->name,
                    'advertiser_name' => $item->advertiser?->name,
                    'status' => $item->status,
                    'weight' => $weight,
                    'intended_share' => round(($weight / $totalWeight) * 100, 1),
                    'impressions' => $impressions,
                    'actual_share' => $totalImpressions > 0 ? round(($impressions / $totalImpressions) * 100, 1) : 0.0,
                    'is_current' => (int) $item->id === (int) $campaign->id,
                ];
            })->values();

            return [
                'placement_id' => $placement->id,
                'placement_name' => $placement->name,
                'placement_code' => $placement->code,
                'days' => $days,
                'total_impressions' => $totalImpressions,
                'campaigns' => $rows,
            ];
        })->values()->all();
    }

    private function weightFor(AdvertisingCampaign $campaign, AdPlacement $placement): int
    {
        $assigned = $campaign->placements->firstWhere('id', $placement->id);

        if (! $assigned) {
            $assigned = $campaign->placements()->whereKey($placement->id)->first();
        }

        return max(1, (int) ($assigned?->pivot?->priority ?? 50));
    }
}
