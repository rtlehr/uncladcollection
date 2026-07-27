<?php

namespace App\Services\SearchIntelligence;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\SearchTerm;
use App\Models\SearchTermVariant;
use Illuminate\Support\Collection;

class SearchTermAggregationService
{
    public function __construct(private readonly SearchTermNormalizer $normalizer) {}

    public function rebuild(?int $days = null): array
    {
        $days ??= (int) config('search-intelligence.lookback_days', 365);
        $events = AnalyticsEvent::query()
            ->where('event_name', AnalyticsEventName::SearchPerformed->value)
            ->where('occurred_at', '>=', now()->subDays(max(1, $days)))
            ->orderBy('occurred_at')
            ->get();

        $grouped = $events->groupBy(fn (AnalyticsEvent $event) => $this->normalizer->normalize((string) data_get($event->dimensions, 'term')))
            ->filter(fn (Collection $items, string $term) => $term !== '');

        $updated = 0;
        foreach ($grouped as $normalized => $items) {
            $display = $items->map(fn ($event) => trim((string) data_get($event->dimensions, 'term')))
                ->filter()->countBy()->sortDesc()->keys()->first() ?: $normalized;
            $results = $items->map(fn ($event) => (int) data_get($event->dimensions, 'result_count', 0));
            $term = SearchTerm::query()->updateOrCreate(
                ['normalized_term' => $normalized],
                [
                    'display_term' => $display,
                    'search_count' => $items->count(),
                    'unique_searchers' => $items->map(fn ($event) => $event->user_id ? 'u:'.$event->user_id : 's:'.($event->session_id ?: $event->id))->unique()->count(),
                    'zero_result_count' => $results->filter(fn ($count) => $count === 0)->count(),
                    'average_results' => round((float) $results->average(), 2),
                    'first_searched_at' => $items->min('occurred_at'),
                    'last_searched_at' => $items->max('occurred_at'),
                ],
            );

            $variants = $items->groupBy(fn ($event) => trim((string) data_get($event->dimensions, 'term')));
            foreach ($variants as $raw => $variantEvents) {
                if ($raw === '') continue;
                SearchTermVariant::query()->updateOrCreate(
                    ['search_term_id' => $term->id, 'raw_term_hash' => hash('sha256', $raw)],
                    [
                        'raw_term' => $raw,
                        'normalized_raw_term' => $this->normalizer->normalize($raw),
                        'search_count' => $variantEvents->count(),
                        'last_searched_at' => $variantEvents->max('occurred_at'),
                    ],
                );
            }

            $this->applyDownstreamMetrics($term, $items);
            $updated++;
        }

        return ['terms' => $updated, 'events' => $events->count()];
    }

    private function applyDownstreamMetrics(SearchTerm $term, Collection $searchEvents): void
    {
        $start = $searchEvents->min('occurred_at');
        $end = $searchEvents->max('occurred_at')?->copy()->addDays(7);
        $visitors = $searchEvents->map(fn ($event) => $event->user_id ? ['user_id', $event->user_id] : ['session_id', $event->session_id])->filter(fn ($pair) => $pair[1])->values();

        $query = AnalyticsEvent::query()->whereBetween('occurred_at', [$start, $end]);
        $query->where(function ($query) use ($visitors): void {
            foreach ($visitors as [$field, $value]) $query->orWhere($field, $value);
        });
        $events = $visitors->isEmpty() ? collect() : $query->get();

        $count = fn (AnalyticsEventName $name) => $events->where('event_name', $name)->count();
        $orders = $events->where('event_name', AnalyticsEventName::OrderPaid);
        $term->update([
            'click_count' => $count(AnalyticsEventName::AssetViewed),
            'favorite_count' => $count(AnalyticsEventName::AssetFavorited),
            'cart_count' => $count(AnalyticsEventName::AssetAddedToCart),
            'order_count' => $orders->count(),
            'revenue_cents' => (int) $orders->sum('value_cents'),
        ]);
    }
}
