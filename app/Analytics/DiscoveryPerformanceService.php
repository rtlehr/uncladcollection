<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DiscoveryPerformanceService
{
    public function report(CarbonInterface $start, CarbonInterface $end): array
    {
        $events = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->whereNotNull('source')
            ->get(['event_name', 'source', 'user_id', 'session_id', 'subject_id', 'value_cents']);

        $sources = $events->groupBy('source')->map(function (Collection $rows, string $source): array {
            $count = fn (AnalyticsEventName $event): int => $rows->filter(fn ($row) => $row->event_name === $event)->count();
            $views = $count(AnalyticsEventName::AssetViewed);
            $favorites = $count(AnalyticsEventName::AssetFavorited);
            $carts = $count(AnalyticsEventName::AssetAddedToCart);
            $orders = $count(AnalyticsEventName::OrderPaid);
            $downloads = $count(AnalyticsEventName::AssetDownloaded);
            $actors = $rows->map(fn ($row) => $row->user_id ? 'u'.$row->user_id : 's'.$row->session_id)->filter()->unique()->count();

            return [
                'source' => $source,
                'views' => $views,
                'favorites' => $favorites,
                'cart_additions' => $carts,
                'orders' => $orders,
                'downloads' => $downloads,
                'unique_actors' => $actors,
                'revenue_cents' => (int) $rows->where('event_name', AnalyticsEventName::OrderPaid)->sum('value_cents'),
                'engagement_rate' => $views > 0 ? round((($favorites + $carts) / $views) * 100, 2) : 0,
                'conversion_rate' => $views > 0 ? round(($orders / $views) * 100, 2) : 0,
            ];
        })->sortByDesc('views')->values();

        return [
            'sources' => $sources->all(),
            'totals' => [
                'views' => $sources->sum('views'),
                'favorites' => $sources->sum('favorites'),
                'cart_additions' => $sources->sum('cart_additions'),
                'orders' => $sources->sum('orders'),
                'downloads' => $sources->sum('downloads'),
                'revenue_cents' => $sources->sum('revenue_cents'),
            ],
        ];
    }
}
