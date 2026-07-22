<?php

namespace App\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\Download;
use App\Models\License;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceMetricsService
{
    public function summary(AnalyticsPeriod $period): array
    {
        $current = $this->metricsFor($period);
        $previous = $this->metricsFor($period->previous());

        return collect($current)->map(function (int|float $value, string $key) use ($previous): array {
            $previousValue = $previous[$key] ?? 0;
            $change = $previousValue == 0 ? null : round((($value - $previousValue) / $previousValue) * 100, 1);

            return ['value' => $value, 'previous_value' => $previousValue, 'change_percent' => $change];
        })->all();
    }

    public function revenueTrend(AnalyticsPeriod $period): array
    {
        return Order::query()
            ->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->selectRaw('DATE(paid_at) as metric_date, SUM(total_cents) as revenue_cents, COUNT(*) as orders_count')
            ->groupByRaw('DATE(paid_at)')
            ->orderBy('metric_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->metric_date,
                'revenue_cents' => (int) $row->revenue_cents,
                'orders_count' => (int) $row->orders_count,
            ])->all();
    }

    private function metricsFor(AnalyticsPeriod $period): array
    {
        $orders = Order::query()->whereBetween('created_at', [$period->start, $period->end]);
        $paidOrders = (clone $orders)->where('status', Order::STATUS_PAID);
        $revenue = (clone $paidOrders)->sum('total_cents');
        $paidCount = (clone $paidOrders)->count();
        $views = AnalyticsEvent::query()->where('event_name', 'asset_viewed')->whereBetween('occurred_at', [$period->start, $period->end])->count();

        return [
            'revenue_cents' => (int) $revenue,
            'orders' => $orders->count(),
            'paid_orders' => $paidCount,
            'average_order_value_cents' => $paidCount > 0 ? (int) round($revenue / $paidCount) : 0,
            'downloads' => Download::query()->whereBetween('downloaded_at', [$period->start, $period->end])->count(),
            'new_users' => User::query()->whereBetween('created_at', [$period->start, $period->end])->count(),
            'published_assets' => Asset::query()->whereBetween('published_at', [$period->start, $period->end])->count(),
            'active_licenses' => License::query()->where('status', License::STATUS_ACTIVE)->whereBetween('created_at', [$period->start, $period->end])->count(),
            'asset_views' => $views,
            'purchase_conversion_percent' => $views > 0 ? round(($paidCount / $views) * 100, 2) : 0,
        ];
    }
}
