<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use Illuminate\Support\Collection;

class SearchDiscoveryService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $rows = $this->termRows($period, $filters);
        $events = $this->searchEvents($period)->get();
        $searches = $events->where('event_name', AnalyticsEventName::SearchPerformed);
        $filterEvents = $events->where('event_name', AnalyticsEventName::SearchFiltersApplied);

        return [
            'summary' => [
                'searches' => $searches->count(),
                'unique_searchers' => $searches->map(fn ($event) => $this->visitorKey($event))->unique()->count(),
                'zero_result_searches' => $rows->sum('zero_result_searches'),
                'low_result_searches' => $rows->sum('low_result_searches'),
                'influenced_orders' => $rows->sum('influenced_orders'),
                'influenced_revenue_cents' => $rows->sum('influenced_revenue_cents'),
            ],
            'terms' => $rows->values()->all(),
            'filter_usage' => $this->filterUsage($filterEvents),
            'sort_usage' => $this->sortUsage($filterEvents),
            'suggestions' => $events->where('event_name', AnalyticsEventName::SearchSuggestionSelected)
                ->groupBy(fn ($event) => data_get($event->dimensions, 'suggestion_type', 'unknown'))
                ->map(fn (Collection $items, string $label) => ['label' => ucfirst($label), 'count' => $items->count()])
                ->sortByDesc('count')->values()->all(),
            'opportunities' => [
                'catalog_gaps' => $rows->filter(fn ($row) => $row['searches'] > 0 && $row['average_results'] < 3)->sortByDesc('searches')->take(10)->values()->all(),
                'high_demand_low_conversion' => $rows->filter(fn ($row) => $row['searches'] >= 3 && $row['search_to_purchase_percent'] < 2)->sortByDesc('searches')->take(10)->values()->all(),
                'revenue_drivers' => $rows->filter(fn ($row) => $row['influenced_revenue_cents'] > 0)->sortByDesc('influenced_revenue_cents')->take(10)->values()->all(),
            ],
        ];
    }

    public function detail(string $term, AnalyticsPeriod $period): array
    {
        $normalized = mb_strtolower(trim($term));
        $row = $this->termRows($period, ['term' => $normalized])->first();
        $timeline = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $start = $date->startOfDay(); $end = $date->endOfDay();
            $events = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::SearchPerformed->value)->whereBetween('occurred_at', [$start, $end])->get()->filter(fn ($event) => data_get($event->dimensions, 'term') === $normalized);
            $userIds = $events->pluck('user_id')->filter()->unique();
            $orders = $userIds->isEmpty() ? collect() : Order::query()->whereIn('user_id', $userIds)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$start, $end])->get();
            $timeline[] = ['date' => $date->toDateString(), 'label' => $date->format('M j'), 'searches' => $events->count(), 'unique_searchers' => $events->map(fn ($event) => $this->visitorKey($event))->unique()->count(), 'orders' => $orders->count(), 'revenue_cents' => (int) $orders->sum('total_cents')];
        }

        return ['term' => $normalized, 'performance' => $row, 'timeline' => $timeline];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters = []): array
    {
        return $this->termRows($period, $filters)->map(fn ($row) => [$row['term'], $row['searches'], $row['unique_searchers'], $row['average_results'], $row['zero_result_searches'], $row['low_result_searches'], $row['registered_searches'], $row['anonymous_searches'], $row['influenced_orders'], $row['influenced_revenue_cents'], $row['search_to_purchase_percent']])->all();
    }

    private function termRows(AnalyticsPeriod $period, array $filters = []): Collection
    {
        $events = AnalyticsEvent::query()->where('event_name', AnalyticsEventName::SearchPerformed->value)->whereBetween('occurred_at', [$period->start, $period->end])->get();
        if (! empty($filters['term'])) $events = $events->filter(fn ($event) => data_get($event->dimensions, 'term') === mb_strtolower($filters['term']));
        if (! empty($filters['search'])) $events = $events->filter(fn ($event) => str_contains((string) data_get($event->dimensions, 'term'), mb_strtolower($filters['search'])));
        if (($filters['result_status'] ?? '') === 'zero') $events = $events->filter(fn ($event) => (int) data_get($event->dimensions, 'result_count', 0) === 0);
        if (($filters['result_status'] ?? '') === 'low') $events = $events->filter(fn ($event) => (int) data_get($event->dimensions, 'result_count', 0) > 0 && (int) data_get($event->dimensions, 'result_count', 0) < 5);

        return $events->groupBy(fn ($event) => (string) data_get($event->dimensions, 'term', '(blank)'))->map(function (Collection $items, string $term) use ($period) {
            $userIds = $items->pluck('user_id')->filter()->unique();
            $orders = $userIds->isEmpty() ? collect() : Order::query()->whereIn('user_id', $userIds)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end])->get();
            $searches = $items->count();
            $results = $items->map(fn ($event) => (int) data_get($event->dimensions, 'result_count', 0));
            return [
                'term' => $term,
                'searches' => $searches,
                'unique_searchers' => $items->map(fn ($event) => $this->visitorKey($event))->unique()->count(),
                'average_results' => round((float) $results->average(), 1),
                'zero_result_searches' => $results->filter(fn ($count) => $count === 0)->count(),
                'low_result_searches' => $results->filter(fn ($count) => $count > 0 && $count < 5)->count(),
                'registered_searches' => $items->whereNotNull('user_id')->count(),
                'anonymous_searches' => $items->whereNull('user_id')->count(),
                'influenced_buyers' => $orders->pluck('user_id')->unique()->count(),
                'influenced_orders' => $orders->count(),
                'influenced_revenue_cents' => (int) $orders->sum('total_cents'),
                'search_to_purchase_percent' => $searches > 0 ? round(($orders->count() / $searches) * 100, 1) : 0,
            ];
        })->sortByDesc(fn ($row) => ($row['influenced_revenue_cents'] * 1000000) + ($row['searches'] * 1000))->values();
    }

    private function searchEvents(AnalyticsPeriod $period)
    {
        return AnalyticsEvent::query()->whereIn('event_name', [AnalyticsEventName::SearchPerformed->value, AnalyticsEventName::SearchFiltersApplied->value, AnalyticsEventName::SearchSuggestionSelected->value])->whereBetween('occurred_at', [$period->start, $period->end]);
    }

    private function filterUsage(Collection $events): array
    {
        $counts = [];
        foreach ($events as $event) foreach ((array) data_get($event->dimensions, 'filters', []) as $key => $value) if (! in_array($key, ['search', 'sort'], true) && $value !== '') $counts[$key] = ($counts[$key] ?? 0) + 1;
        arsort($counts);
        return collect($counts)->map(fn ($count, $key) => ['label' => str($key)->replace('_', ' ')->title()->toString(), 'count' => $count])->values()->all();
    }

    private function sortUsage(Collection $events): array
    {
        return $events->groupBy(fn ($event) => data_get($event->dimensions, 'filters.sort', 'newest'))->map(fn ($items, $label) => ['label' => str($label)->replace('_', ' ')->title()->toString(), 'count' => $items->count()])->sortByDesc('count')->values()->all();
    }

    private function visitorKey(AnalyticsEvent $event): string
    {
        return $event->user_id ? 'user:'.$event->user_id : ($event->session_id ? 'session:'.$event->session_id : 'event:'.$event->id);
    }
}
