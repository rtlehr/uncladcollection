<?php

namespace App\Services;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetTrendingScore;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrendingAssetService
{
    public function __construct(
        private readonly DiscoveryCacheService $cache,
        private readonly RelatedAssetService $formatter,
    ) {}

    public function rebuild(?string $onlyPeriod = null): array
    {
        $periods = collect(config('discovery.trending.periods', []))
            ->when($onlyPeriod, fn (Collection $items) => $items->only($onlyPeriod));

        $summary = [];

        foreach ($periods as $period => $days) {
            $summary[$period] = $this->rebuildPeriod((string) $period, (int) $days);
        }

        $this->cache->invalidate();

        return $summary;
    }

    public function rebuildPeriod(string $period, int $days): int
    {
        $cutoff = now()->subDays(max(1, $days));
        $weights = config('discovery.trending.weights', []);
        $halfLifeHours = max(1, (float) config('discovery.trending.half_life_hours', 72));
        $assetMorph = (new Asset())->getMorphClass();

        $rows = AnalyticsEvent::query()
            ->where('subject_type', $assetMorph)
            ->whereNotNull('subject_id')
            ->where('occurred_at', '>=', $cutoff)
            ->whereIn('event_name', [
                AnalyticsEventName::AssetViewed->value,
                AnalyticsEventName::AssetFavorited->value,
                AnalyticsEventName::AssetAddedToCart->value,
                AnalyticsEventName::AssetDownloaded->value,
            ])
            ->get(['subject_id', 'event_name', 'user_id', 'session_id', 'occurred_at']);

        $scores = [];
        foreach ($rows->groupBy(function (AnalyticsEvent $event): string {
            $eventName = $this->eventNameValue($event->event_name);

            return $event->subject_id.'|'.$eventName.'|'.($event->user_id ? 'u'.$event->user_id : 's'.$event->session_id);
        }) as $events) {
            /** @var AnalyticsEvent $event */
            $event = $events->sortByDesc('occurred_at')->first();
            $eventName = $this->eventNameValue($event->event_name);
            $ageHours = max(0, $event->occurred_at->diffInMinutes(now()) / 60);
            $decay = pow(0.5, $ageHours / $halfLifeHours);
            $weight = (float) ($weights[$eventName] ?? 0);
            $scores[(int) $event->subject_id][$eventName] =
                ($scores[(int) $event->subject_id][$eventName] ?? 0) + ($weight * $decay);
        }

        $purchaseRows = OrderItem::query()
            ->select([
                'order_items.asset_id',
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('SUM(order_items.total_price_cents) as revenue_cents'),
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNotNull('order_items.asset_id')
            ->whereNotNull('orders.paid_at')
            ->where('orders.paid_at', '>=', $cutoff)
            ->groupBy('order_items.asset_id')
            ->get();

        foreach ($purchaseRows as $row) {
            $scores[(int) $row->asset_id]['purchases'] =
                ((int) $row->purchase_count * (float) ($weights['purchases'] ?? 20))
                + min(25, ((int) $row->revenue_cents / 10000));
        }

        $assets = Asset::query()
            ->discoverable()
            ->where('suppress_from_trending', false)
            ->get(['id', 'trending_boost', 'is_featured']);

        $ranked = $assets->map(function (Asset $asset) use ($scores): array {
            $components = $scores[$asset->id] ?? [];
            $components['editorial_boost'] = (float) $asset->trending_boost;
            $components['featured_boost'] = $asset->is_featured
                ? (float) config('discovery.trending.featured_boost', 3)
                : 0;

            return [
                'asset_id' => $asset->id,
                'score' => round(array_sum($components), 4),
                'components' => $components,
            ];
        })->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();

        DB::transaction(function () use ($period, $ranked): void {
            AssetTrendingScore::query()->where('period', $period)->delete();

            foreach ($ranked as $index => $row) {
                AssetTrendingScore::query()->create([
                    'asset_id' => $row['asset_id'],
                    'period' => $period,
                    'score' => $row['score'],
                    'rank' => $index + 1,
                    'components' => $row['components'],
                    'calculated_at' => now(),
                ]);
            }
        });

        return $ranked->count();
    }

    private function eventNameValue(AnalyticsEventName|string $eventName): string
    {
        return $eventName instanceof AnalyticsEventName ? $eventName->value : $eventName;
    }

    public function assets(string $period = 'now', int $limit = 8): Collection
    {
        $key = $this->cache->key('trending-assets', compact('period', 'limit'));
        $ttl = now()->addMinutes((int) config('discovery.trending.cache_minutes', 15));
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return collect($cached);
        }

        // Older releases cached a Collection object. Serialized objects can become
        // __PHP_Incomplete_Class after deployments or class-map changes, so remove
        // any non-array value and rebuild it as deployment-safe scalar data.
        if ($cached !== null) {
            Cache::forget($key);
        }

        $assets = AssetTrendingScore::query()
            ->where('period', $period)
            ->whereHas('asset', fn ($query) => $query->discoverable()->where('suppress_from_trending', false))
            ->with(['asset.collection:id,name,slug', 'asset.activeFiles', 'asset.primaryPreviewFile', 'asset.posterFile'])
            ->orderBy('rank')
            ->limit($limit)
            ->get()
            ->map(fn (AssetTrendingScore $score) => $this->formatter->format(
                $score->asset,
                'Trending '.match ($period) {
                    'now' => 'now',
                    'week' => 'this week',
                    default => 'this month',
                },
                $score->score,
                'trending_'.$period,
            ))
            ->values();

        Cache::put($key, $assets->all(), $ttl);

        return $assets;
    }
}
