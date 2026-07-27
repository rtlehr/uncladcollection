<?php

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\SearchTerm;
use App\Models\SearchTermMapping;
use App\Services\SearchIntelligence\SearchTermAggregationService;
use App\Services\SearchIntelligence\SearchTermResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('aggregates raw search variants without losing the original terms', function (): void {
    foreach (['Camp Ground', 'camp-ground', 'camp ground'] as $index => $raw) {
        AnalyticsEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_name' => AnalyticsEventName::SearchPerformed,
            'session_id' => 'session-'.$index,
            'dimensions' => ['term' => $raw, 'result_count' => $index === 0 ? 0 : 4],
            'occurred_at' => now()->subMinutes($index),
        ]);
    }

    app(SearchTermAggregationService::class)->rebuild(30);

    $term = SearchTerm::query()->where('normalized_term', 'camp ground')->firstOrFail();

    $actualVariants = $term->variants()
        ->pluck('raw_term')
        ->sort(SORT_STRING)
        ->values()
        ->all();

    $expectedVariants = collect([
        'Camp Ground',
        'camp-ground',
        'camp ground',
    ])->sort(SORT_STRING)->values()->all();

    expect($term->search_count)->toBe(3)
        ->and($term->zero_result_count)->toBe(1)
        ->and($term->variants()->count())->toBe(3)
        ->and($actualVariants)->toBe($expectedVariants);
});

it('applies only approved canonical terms and synonyms', function (): void {
    $term = SearchTerm::query()->create([
        'normalized_term' => 'campgroud',
        'display_term' => 'campgroud',
    ]);

    SearchTermMapping::query()->create([
        'search_term_id' => $term->id,
        'suggested_canonical_term' => 'campground',
        'suggested_synonyms' => ['camp ground'],
        'status' => SearchTermMapping::STATUS_PENDING,
    ]);

    expect(app(SearchTermResolver::class)->resolve('campgroud')['canonical'])->toBe('campgroud');

    $term->mapping()->update([
        'status' => SearchTermMapping::STATUS_APPROVED,
        'approved_canonical_term' => 'campground',
        'approved_synonyms' => ['camp ground', 'naturist campground'],
    ]);

    app(SearchTermResolver::class)->flush('campgroud');

    $resolved = app(SearchTermResolver::class)->resolve('campgroud');

    expect($resolved['canonical'])->toBe('campground')
        ->and($resolved['terms'])->toContain('naturist campground');
});
