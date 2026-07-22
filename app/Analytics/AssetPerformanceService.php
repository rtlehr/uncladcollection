<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\Download;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssetPerformanceService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $rows = $this->assetRows($period, $filters);

        return [
            'summary' => $this->summary($rows),
            'assets' => $rows->values()->all(),
            'media_types' => $this->groupedPerformance($rows, 'asset_type'),
            'collections' => $this->groupedPerformance($rows, 'collection_name'),
            'opportunities' => $this->opportunities($rows),
        ];
    }

    public function detail(Asset $asset, AnalyticsPeriod $period): array
    {
        $row = $this->assetRows($period, ['asset_id' => $asset->id])->first();

        $dailyEvents = AnalyticsEvent::query()
            ->where('subject_type', $asset->getMorphClass())
            ->where('subject_id', $asset->id)
            ->whereBetween('occurred_at', [$period->start, $period->end])
            ->selectRaw('DATE(occurred_at) metric_date, event_name, COUNT(*) aggregate')
            ->groupByRaw('DATE(occurred_at), event_name')
            ->get()
            ->groupBy('metric_date');

        $dailySales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.asset_id', $asset->id)
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw('DATE(orders.paid_at) metric_date, SUM(order_items.quantity) units, SUM(order_items.total_price_cents) revenue_cents')
            ->groupByRaw('DATE(orders.paid_at)')
            ->get()->keyBy('metric_date');

        $timeline = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $events = $this->eventCounts($dailyEvents->get($key, collect()));
            $sales = $dailySales->get($key);
            $timeline[] = [
                'date' => $key,
                'label' => $date->format('M j'),
                'views' => (int) ($events[AnalyticsEventName::AssetViewed->value] ?? 0),
                'favorites' => (int) ($events[AnalyticsEventName::AssetFavorited->value] ?? 0),
                'cart_additions' => (int) ($events[AnalyticsEventName::AssetAddedToCart->value] ?? 0),
                'units' => (int) ($sales?->units ?? 0),
                'revenue_cents' => (int) ($sales?->revenue_cents ?? 0),
            ];
        }

        $licenseMix = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.asset_id', $asset->id)
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw("COALESCE(order_items.license_name, 'Unspecified') label, SUM(order_items.quantity) units, SUM(order_items.total_price_cents) revenue_cents")
            ->groupBy('label')->orderByDesc('revenue_cents')->get()
            ->map(fn ($item) => ['label' => $item->label, 'units' => (int) $item->units, 'revenue_cents' => (int) $item->revenue_cents])
            ->all();

        return [
            'asset' => [
                'id' => $asset->id,
                'title' => $asset->title,
                'slug' => $asset->slug,
                'asset_type' => $asset->asset_type?->value ?? (string) $asset->asset_type,
                'status' => $asset->status?->value ?? (string) $asset->status,
                'collection' => $asset->collection?->name,
                'published_at' => $asset->published_at?->toIso8601String(),
            ],
            'performance' => $row,
            'timeline' => $timeline,
            'license_mix' => $licenseMix,
            'offerings' => $asset->offerings()->with('licenseType:id,name')->get()->map(fn ($offering) => [
                'id' => $offering->id,
                'name' => $offering->name,
                'license' => $offering->licenseType?->name,
                'is_active' => (bool) $offering->is_active,
                'image_units' => (int) $offering->image_units,
                'video_units' => (int) $offering->video_units,
            ])->all(),
        ];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters = []): array
    {
        return $this->assetRows($period, $filters)->map(fn (array $row) => [
            $row['asset_id'], $row['title'], $row['asset_type'], $row['collection_name'],
            $row['views'], $row['favorites'], $row['cart_additions'], $row['units_sold'],
            $row['revenue_cents'], $row['downloads'], $row['view_to_cart_percent'],
            $row['view_to_purchase_percent'], $row['revenue_per_view_cents'],
        ])->all();
    }

    private function assetRows(AnalyticsPeriod $period, array $filters = []): Collection
    {
        $events = AnalyticsEvent::query()
            ->where('subject_type', (new Asset())->getMorphClass())
            ->whereBetween('occurred_at', [$period->start, $period->end])
            ->whereIn('event_name', [
                AnalyticsEventName::AssetViewed->value,
                AnalyticsEventName::AssetFavorited->value,
                AnalyticsEventName::AssetAddedToCart->value,
            ])
            ->selectRaw('subject_id, event_name, COUNT(*) aggregate')
            ->groupBy('subject_id', 'event_name')->get()->groupBy('subject_id');

        $sales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('order_items.asset_id')
            ->where('orders.status', Order::STATUS_PAID)
            ->whereBetween('orders.paid_at', [$period->start, $period->end])
            ->selectRaw('order_items.asset_id, SUM(order_items.quantity) units, SUM(order_items.total_price_cents) revenue_cents, COUNT(DISTINCT orders.id) orders_count')
            ->groupBy('order_items.asset_id')->get()->keyBy('asset_id');

        $downloads = Download::query()
            ->join('order_items', 'order_items.id', '=', 'downloads.order_item_id')
            ->whereNotNull('order_items.asset_id')
            ->whereBetween('downloads.downloaded_at', [$period->start, $period->end])
            ->selectRaw('order_items.asset_id, COUNT(*) aggregate')
            ->groupBy('order_items.asset_id')->pluck('aggregate', 'asset_id');

        $query = Asset::query()->with('collection:id,name')->withCount(['offerings as active_offerings_count' => fn (Builder $q) => $q->where('is_active', true)]);
        if (!empty($filters['asset_id'])) $query->whereKey($filters['asset_id']);
        if (!empty($filters['search'])) $query->where(fn (Builder $q) => $q->where('title', 'like', '%'.$filters['search'].'%')->orWhere('slug', 'like', '%'.$filters['search'].'%'));
        if (!empty($filters['asset_type']) && $filters['asset_type'] !== 'all') $query->where('asset_type', $filters['asset_type']);
        if (!empty($filters['collection_id'])) $query->where('collection_id', $filters['collection_id']);

        return $query->get()->map(function (Asset $asset) use ($events, $sales, $downloads): array {
            $assetEvents = $this->eventCounts($events->get($asset->id, collect()));
            $views = (int) ($assetEvents[AnalyticsEventName::AssetViewed->value] ?? 0);
            $favorites = (int) ($assetEvents[AnalyticsEventName::AssetFavorited->value] ?? 0);
            $carts = (int) ($assetEvents[AnalyticsEventName::AssetAddedToCart->value] ?? 0);
            $sale = $sales->get($asset->id);
            $units = (int) ($sale?->units ?? 0);
            $revenue = (int) ($sale?->revenue_cents ?? 0);

            return [
                'asset_id' => $asset->id,
                'title' => $asset->title,
                'slug' => $asset->slug,
                'asset_type' => $asset->asset_type?->value ?? (string) $asset->asset_type,
                'collection_id' => $asset->collection_id,
                'collection_name' => $asset->collection?->name ?? 'Unassigned',
                'is_published' => $asset->status?->value === 'published' && $asset->is_active,
                'active_offerings' => (int) $asset->active_offerings_count,
                'views' => $views,
                'favorites' => $favorites,
                'cart_additions' => $carts,
                'orders' => (int) ($sale?->orders_count ?? 0),
                'units_sold' => $units,
                'revenue_cents' => $revenue,
                'downloads' => (int) ($downloads[$asset->id] ?? 0),
                'view_to_cart_percent' => $views > 0 ? round(($carts / $views) * 100, 1) : 0,
                'view_to_purchase_percent' => $views > 0 ? round(($units / $views) * 100, 1) : 0,
                'cart_to_purchase_percent' => $carts > 0 ? round(($units / $carts) * 100, 1) : 0,
                'revenue_per_view_cents' => $views > 0 ? (int) round($revenue / $views) : 0,
                'lifetime_views' => (int) $asset->views_count,
                'lifetime_favorites' => (int) $asset->favorites_count,
                'lifetime_purchases' => (int) $asset->purchases_count,
                'lifetime_downloads' => (int) $asset->downloads_count,
            ];
        })->sortByDesc(fn (array $row) => [$row['revenue_cents'], $row['units_sold'], $row['views']])->values();
    }

    private function summary(Collection $rows): array
    {
        $views = (int) $rows->sum('views');
        $carts = (int) $rows->sum('cart_additions');
        $units = (int) $rows->sum('units_sold');
        return [
            'assets_measured' => $rows->count(),
            'views' => $views,
            'cart_additions' => $carts,
            'units_sold' => $units,
            'revenue_cents' => (int) $rows->sum('revenue_cents'),
            'downloads' => (int) $rows->sum('downloads'),
            'view_to_cart_percent' => $views > 0 ? round(($carts / $views) * 100, 1) : 0,
            'view_to_purchase_percent' => $views > 0 ? round(($units / $views) * 100, 1) : 0,
        ];
    }

    private function groupedPerformance(Collection $rows, string $key): array
    {
        return $rows->groupBy($key)->map(function (Collection $group, string $label): array {
            $views = (int) $group->sum('views');
            $units = (int) $group->sum('units_sold');
            return ['label' => str($label)->replace('_', ' ')->title()->toString(), 'assets' => $group->count(), 'views' => $views, 'units' => $units, 'revenue_cents' => (int) $group->sum('revenue_cents'), 'conversion_percent' => $views > 0 ? round(($units / $views) * 100, 1) : 0];
        })->sortByDesc('revenue_cents')->values()->all();
    }

    private function opportunities(Collection $rows): array
    {
        return [
            'high_traffic_low_conversion' => $rows->filter(fn (array $row) => $row['views'] >= 5 && $row['view_to_purchase_percent'] < 2)->sortByDesc('views')->take(8)->values()->all(),
            'selling_with_low_traffic' => $rows->filter(fn (array $row) => $row['units_sold'] > 0 && $row['views'] < 10)->sortByDesc('revenue_cents')->take(8)->values()->all(),
            'published_without_offerings' => $rows->filter(fn (array $row) => $row['is_published'] && $row['active_offerings'] === 0)->take(8)->values()->all(),
            'no_activity' => $rows->filter(fn (array $row) => $row['views'] === 0 && $row['units_sold'] === 0)->take(8)->values()->all(),
        ];
    }
    private function eventCounts(Collection $events): Collection
    {
        return $events->mapWithKeys(function ($event): array {
            $eventName = $event->event_name instanceof AnalyticsEventName
                ? $event->event_name->value
                : (string) $event->event_name;

            return [$eventName => (int) $event->aggregate];
        });
    }

}
