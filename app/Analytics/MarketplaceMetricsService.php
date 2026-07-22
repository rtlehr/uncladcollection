<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Enums\AssetStatus;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\Download;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketplaceMetricsService
{
    public function dashboard(AnalyticsPeriod $period): array
    {
        return [
            'summary' => $this->summary($period),
            'revenue_trend' => $this->revenueTrend($period),
            'conversion_funnel' => $this->conversionFunnel($period),
            'license_mix' => $this->licenseMix($period),
            'media_mix' => $this->mediaMix($period),
            'top_assets' => $this->topAssets($period),
            'marketplace_health' => $this->marketplaceHealth($period),
            'recent_activity' => $this->recentActivity($period),
        ];
    }

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
        $rows = Order::query()
            ->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->selectRaw('DATE(paid_at) as metric_date, SUM(total_cents) as revenue_cents, COUNT(*) as orders_count')
            ->groupByRaw('DATE(paid_at)')
            ->orderBy('metric_date')
            ->get()
            ->keyBy('metric_date');

        $points = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);
            $points[] = [
                'date' => $key,
                'label' => $date->format($period->start->year === $period->end->year ? 'M j' : 'M j, Y'),
                'revenue_cents' => (int) ($row?->revenue_cents ?? 0),
                'orders_count' => (int) ($row?->orders_count ?? 0),
            ];
        }

        return $points;
    }

    public function conversionFunnel(AnalyticsPeriod $period): array
    {
        $eventCounts = AnalyticsEvent::query()
            ->whereBetween('occurred_at', [$period->start, $period->end])
            ->whereIn('event_name', [
                AnalyticsEventName::AssetViewed->value,
                AnalyticsEventName::AssetAddedToCart->value,
                AnalyticsEventName::CheckoutStarted->value,
            ])
            ->selectRaw('event_name, COUNT(*) as aggregate')
            ->groupBy('event_name')
            ->pluck('aggregate', 'event_name');

        $views = (int) ($eventCounts[AnalyticsEventName::AssetViewed->value] ?? 0);
        $carts = (int) ($eventCounts[AnalyticsEventName::AssetAddedToCart->value] ?? 0);
        $checkouts = (int) ($eventCounts[AnalyticsEventName::CheckoutStarted->value] ?? 0);
        $purchases = Order::query()->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end])->count();

        $stages = [
            ['key' => 'views', 'label' => 'Asset views', 'value' => $views],
            ['key' => 'carts', 'label' => 'Added to cart', 'value' => $carts],
            ['key' => 'checkouts', 'label' => 'Checkout started', 'value' => $checkouts],
            ['key' => 'purchases', 'label' => 'Paid orders', 'value' => $purchases],
        ];

        return collect($stages)->map(function (array $stage, int $index) use ($stages): array {
            $previous = $index === 0 ? $stage['value'] : $stages[$index - 1]['value'];
            $stage['conversion_percent'] = $index === 0 ? 100 : ($previous > 0 ? round(($stage['value'] / $previous) * 100, 1) : 0);
            return $stage;
        })->all();
    }

    public function licenseMix(AnalyticsPeriod $period): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('license_types', 'license_types.id', '=', 'order_items.license_type_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw("COALESCE(license_types.name, order_items.license_name, 'Unspecified') as label, SUM(order_items.total_price_cents) as revenue_cents, SUM(order_items.quantity) as units")
            ->groupByRaw("COALESCE(license_types.name, order_items.license_name, 'Unspecified')")
            ->orderByDesc('revenue_cents')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'revenue_cents' => (int) $row->revenue_cents, 'units' => (int) $row->units])
            ->all();
    }

    public function mediaMix(AnalyticsPeriod $period): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('assets', 'assets.id', '=', 'order_items.asset_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw("COALESCE(assets.asset_type, 'legacy_image') as label, SUM(order_items.total_price_cents) as revenue_cents, SUM(order_items.quantity) as units")
            ->groupByRaw("COALESCE(assets.asset_type, 'legacy_image')")
            ->orderByDesc('revenue_cents')
            ->get()
            ->map(fn ($row) => [
                'label' => str($row->label)->replace('_', ' ')->title()->toString(),
                'revenue_cents' => (int) $row->revenue_cents,
                'units' => (int) $row->units,
            ])->all();
    }

    public function topAssets(AnalyticsPeriod $period): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('assets', 'assets.id', '=', 'order_items.asset_id')
            ->whereNotNull('order_items.asset_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw("order_items.asset_id, COALESCE(assets.title, order_items.asset_title, 'Untitled asset') as title, assets.slug, SUM(order_items.quantity) as units, SUM(order_items.total_price_cents) as revenue_cents")
            ->groupBy('order_items.asset_id', 'assets.title', 'order_items.asset_title', 'assets.slug')
            ->orderByDesc('revenue_cents')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'asset_id' => (int) $row->asset_id,
                'title' => $row->title,
                'slug' => $row->slug,
                'units' => (int) $row->units,
                'revenue_cents' => (int) $row->revenue_cents,
            ])->all();
    }

    public function marketplaceHealth(AnalyticsPeriod $period): array
    {
        $orders = Order::query()->whereBetween('created_at', [$period->start, $period->end]);
        $totalOrders = (clone $orders)->count();
        $failedOrders = (clone $orders)->where('status', Order::STATUS_FAILED)->count();
        $refundedOrders = (clone $orders)->whereIn('status', [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])->count();

        return [
            'published_assets' => Asset::query()->published()->count(),
            'active_offerings' => AssetOffering::query()->where('is_active', true)->count(),
            'active_licenses' => License::query()->where('status', License::STATUS_ACTIVE)->count(),
            'failed_order_rate_percent' => $totalOrders > 0 ? round(($failedOrders / $totalOrders) * 100, 1) : 0,
            'refund_rate_percent' => $totalOrders > 0 ? round(($refundedOrders / $totalOrders) * 100, 1) : 0,
            'downloads_per_paid_order' => $this->downloadsPerPaidOrder($period),
        ];
    }

    public function recentActivity(AnalyticsPeriod $period): array
    {
        $orders = Order::query()->with('user:id,name')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->latest()->limit(6)->get()->map(fn (Order $order) => [
                'type' => 'order',
                'title' => $order->status === Order::STATUS_PAID ? 'Order paid' : 'Order created',
                'description' => trim(($order->order_number ?? 'Order').' · '.($order->user?->name ?? 'Guest')),
                'amount_cents' => (int) $order->total_cents,
                'occurred_at' => $order->paid_at?->toIso8601String() ?? $order->created_at->toIso8601String(),
                'href' => '/admin/orders/'.$order->id,
            ]);

        $downloads = Download::query()->with('user:id,name')
            ->whereBetween('downloaded_at', [$period->start, $period->end])
            ->latest('downloaded_at')->limit(6)->get()->map(fn (Download $download) => [
                'type' => 'download',
                'title' => 'Asset downloaded',
                'description' => $download->user?->name ?? 'Customer',
                'amount_cents' => null,
                'occurred_at' => $download->downloaded_at?->toIso8601String(),
                'href' => '/admin/downloads',
            ]);

        return $orders->concat($downloads)
            ->sortByDesc('occurred_at')->take(8)->values()->all();
    }

    private function metricsFor(AnalyticsPeriod $period): array
    {
        $orders = Order::query()->whereBetween('created_at', [$period->start, $period->end]);
        $paidOrders = Order::query()->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end]);
        $revenue = (clone $paidOrders)->sum('total_cents');
        $paidCount = (clone $paidOrders)->count();
        $views = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::AssetViewed->value)->whereBetween('occurred_at', [$period->start, $period->end])->count();

        return [
            'revenue_cents' => (int) $revenue,
            'orders' => $orders->count(),
            'paid_orders' => $paidCount,
            'average_order_value_cents' => $paidCount > 0 ? (int) round($revenue / $paidCount) : 0,
            'downloads' => Download::query()->whereBetween('downloaded_at', [$period->start, $period->end])->count(),
            'new_users' => User::query()->whereBetween('created_at', [$period->start, $period->end])->count(),
            'published_assets' => Asset::query()->where('status', AssetStatus::Published)->whereBetween('published_at', [$period->start, $period->end])->count(),
            'active_licenses' => License::query()->where('status', License::STATUS_ACTIVE)->whereBetween('created_at', [$period->start, $period->end])->count(),
            'asset_views' => $views,
            'purchase_conversion_percent' => $views > 0 ? round(($paidCount / $views) * 100, 2) : 0,
        ];
    }

    private function downloadsPerPaidOrder(AnalyticsPeriod $period): float
    {
        $paidOrders = Order::query()->where('status', Order::STATUS_PAID)
            ->whereBetween('paid_at', [$period->start, $period->end])->count();
        $downloads = Download::query()->whereBetween('downloaded_at', [$period->start, $period->end])->count();

        return $paidOrders > 0 ? round($downloads / $paidOrders, 1) : 0;
    }
}
