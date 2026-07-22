<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\MarketingCampaign;
use App\Models\Order;
use Illuminate\Support\Collection;

class MarketingCampaignPerformanceService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $rows = $this->campaignRows($period, $filters);
        $impressions = (int) $rows->sum('impressions');
        $clicks = (int) $rows->sum('clicks');

        return [
            'summary' => [
                'campaigns' => $rows->count(),
                'impressions' => $impressions,
                'unique_viewers' => (int) $rows->sum('unique_viewers'),
                'clicks' => $clicks,
                'click_through_rate_percent' => $impressions > 0 ? round(($clicks / $impressions) * 100, 1) : 0,
                'influenced_orders' => (int) $rows->sum('influenced_orders'),
                'influenced_revenue_cents' => (int) $rows->sum('influenced_revenue_cents'),
            ],
            'campaigns' => $rows->values()->all(),
            'media_types' => $rows->groupBy('media_type')->map(fn (Collection $items, string $label) => [
                'label' => ucfirst($label),
                'revenue_cents' => (int) $items->sum('influenced_revenue_cents'),
                'units' => (int) $items->sum('influenced_orders'),
                'views' => (int) $items->sum('impressions'),
            ])->values()->all(),
            'opportunities' => [
                'high_reach_low_clicks' => $rows->filter(fn (array $row) => $row['impressions'] >= 5 && $row['click_through_rate_percent'] < 2)->take(10)->values()->all(),
                'conversion_drivers' => $rows->filter(fn (array $row) => $row['influenced_revenue_cents'] > 0)->sortByDesc('influenced_revenue_cents')->take(10)->values()->all(),
                'no_recent_activity' => $rows->filter(fn (array $row) => $row['impressions'] === 0)->take(10)->values()->all(),
            ],
        ];
    }

    public function detail(MarketingCampaign $campaign, AnalyticsPeriod $period): array
    {
        $row = $this->campaignRows($period, ['campaign_id' => $campaign->id])->first();
        $timeline = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $start = $date->startOfDay(); $end = $date->endOfDay();
            $events = $this->events($campaign, $start, $end)->get();
            $viewers = $events->where('event_name', AnalyticsEventName::CampaignViewed)->map(fn ($e) => $this->visitorKey($e))->unique();
            $clicks = $events->where('event_name', AnalyticsEventName::CampaignClicked);
            $userIds = $events->pluck('user_id')->filter()->unique();
            $orders = $userIds->isEmpty() ? collect() : Order::query()->whereIn('user_id', $userIds)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$start, $end])->get();
            $timeline[] = ['date' => $date->toDateString(), 'label' => $date->format('M j'), 'impressions' => $events->where('event_name', AnalyticsEventName::CampaignViewed)->count(), 'unique_viewers' => $viewers->count(), 'clicks' => $clicks->count(), 'orders' => $orders->count(), 'revenue_cents' => (int) $orders->sum('total_cents')];
        }

        return [
            'campaign' => ['id' => $campaign->id, 'name' => $campaign->name, 'media_type' => $campaign->media_type, 'headline' => $campaign->headline, 'is_active' => $campaign->is_active, 'is_current' => $campaign->is_current, 'starts_at' => $campaign->starts_at?->toIso8601String(), 'ends_at' => $campaign->ends_at?->toIso8601String()],
            'performance' => $row,
            'timeline' => $timeline,
        ];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters = []): array
    {
        return $this->campaignRows($period, $filters)->map(fn (array $r) => [$r['campaign_id'], $r['name'], $r['media_type'], $r['status'], $r['impressions'], $r['unique_viewers'], $r['clicks'], $r['primary_clicks'], $r['secondary_clicks'], $r['click_through_rate_percent'], $r['influenced_buyers'], $r['influenced_orders'], $r['influenced_revenue_cents'], $r['revenue_per_viewer_cents']])->all();
    }

    private function campaignRows(AnalyticsPeriod $period, array $filters = []): Collection
    {
        $query = MarketingCampaign::query();
        if (! empty($filters['campaign_id'])) $query->whereKey($filters['campaign_id']);
        if (! empty($filters['search'])) $query->where(fn ($q) => $q->where('name', 'like', '%'.$filters['search'].'%')->orWhere('headline', 'like', '%'.$filters['search'].'%'));
        if (! empty($filters['media_type'])) $query->where('media_type', $filters['media_type']);
        if (($filters['status'] ?? '') === 'active') $query->where('is_active', true);
        if (($filters['status'] ?? '') === 'inactive') $query->where('is_active', false);

        return $query->get()->map(function (MarketingCampaign $campaign) use ($period) {
            $events = $this->events($campaign, $period->start, $period->end)->get();
            $views = $events->where('event_name', AnalyticsEventName::CampaignViewed);
            $clicks = $events->where('event_name', AnalyticsEventName::CampaignClicked);
            $userIds = $events->pluck('user_id')->filter()->unique();
            $orders = $userIds->isEmpty() ? collect() : Order::query()->whereIn('user_id', $userIds)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end])->get();
            $impressions = $views->count(); $unique = $views->map(fn ($e) => $this->visitorKey($e))->unique()->count();
            return [
                'campaign_id' => $campaign->id, 'name' => $campaign->name, 'headline' => $campaign->headline, 'media_type' => $campaign->media_type,
                'status' => $campaign->is_current ? 'Current' : ($campaign->is_active ? 'Scheduled' : 'Inactive'), 'is_current' => $campaign->is_current,
                'impressions' => $impressions, 'unique_viewers' => $unique, 'clicks' => $clicks->count(),
                'primary_clicks' => $clicks->filter(fn ($e) => data_get($e->dimensions, 'button') === 'primary')->count(),
                'secondary_clicks' => $clicks->filter(fn ($e) => data_get($e->dimensions, 'button') === 'secondary')->count(),
                'click_through_rate_percent' => $impressions > 0 ? round(($clicks->count() / $impressions) * 100, 1) : 0,
                'influenced_buyers' => $orders->pluck('user_id')->unique()->count(), 'influenced_orders' => $orders->count(),
                'influenced_revenue_cents' => (int) $orders->sum('total_cents'),
                'revenue_per_viewer_cents' => $unique > 0 ? (int) round($orders->sum('total_cents') / $unique) : 0,
            ];
        })->sortByDesc(fn (array $r) => ($r['influenced_revenue_cents'] * 1000000) + ($r['clicks'] * 1000) + $r['impressions'])->values();
    }

    private function events(MarketingCampaign $campaign, $start, $end)
    {
        return AnalyticsEvent::query()->whereIn('event_name', [AnalyticsEventName::CampaignViewed->value, AnalyticsEventName::CampaignClicked->value])->where('subject_type', $campaign->getMorphClass())->where('subject_id', $campaign->id)->whereBetween('occurred_at', [$start, $end]);
    }

    private function visitorKey(AnalyticsEvent $event): string
    {
        return $event->user_id ? 'user:'.$event->user_id : ($event->session_id ? 'session:'.$event->session_id : 'event:'.$event->id);
    }
}
