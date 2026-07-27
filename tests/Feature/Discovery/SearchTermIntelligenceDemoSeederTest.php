<?php

use App\Models\AnalyticsEvent;
use App\Models\SearchTerm;
use App\Models\SearchTermMapping;
use Database\Seeders\SearchTermIntelligenceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a rerunnable set of search intelligence examples', function (): void {
    $this->seed(SearchTermIntelligenceDemoSeeder::class);
    $firstEventCount = AnalyticsEvent::query()->where('source', SearchTermIntelligenceDemoSeeder::SOURCE)->count();

    $this->seed(SearchTermIntelligenceDemoSeeder::class);

    expect($firstEventCount)->toBeGreaterThan(0)
        ->and(AnalyticsEvent::query()->where('source', SearchTermIntelligenceDemoSeeder::SOURCE)->count())->toBe($firstEventCount)
        ->and(SearchTerm::query()->where('is_content_opportunity', true)->count())->toBeGreaterThanOrEqual(3)
        ->and(SearchTermMapping::query()->where('status', SearchTermMapping::STATUS_APPROVED)->count())->toBeGreaterThanOrEqual(4)
        ->and(SearchTermMapping::query()->where('status', SearchTermMapping::STATUS_PENDING)->count())->toBeGreaterThanOrEqual(3)
        ->and(SearchTermMapping::query()->where('status', SearchTermMapping::STATUS_REJECTED)->exists())->toBeTrue();
});

it('preserves exact spelling variants in the seeded examples', function (): void {
    $this->seed(SearchTermIntelligenceDemoSeeder::class);

    $term = SearchTerm::query()->where('normalized_term', 'camp ground')->firstOrFail();
    $variants = $term->variants()->pluck('raw_term')->all();

    expect($variants)->toContain('Camp Ground', 'camp-ground', 'camp ground')
        ->and(SearchTerm::query()->where('normalized_term', 'campgroud')->exists())->toBeTrue();
});
