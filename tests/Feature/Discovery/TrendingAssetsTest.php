<?php

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetTrendingScore;
use App\Services\TrendingAssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createDiscoverableTrendingAsset(array $attributes = []): Asset
{
    $asset = Asset::factory()->create(array_merge([
        'status' => 'published',
        'is_active' => true,
        'published_at' => now()->subDay(),
    ], $attributes));

    AssetFile::factory()->preview()->for($asset)->create();

    return $asset;
}

it('ranks recent high intent activity above an older view', function (): void {
    $recent = createDiscoverableTrendingAsset();
    $older = createDiscoverableTrendingAsset();

    foreach ([
        [$recent, AnalyticsEventName::AssetFavorited, now()->subHour()],
        [$recent, AnalyticsEventName::AssetAddedToCart, now()->subHours(2)],
        [$older, AnalyticsEventName::AssetViewed, now()->subDays(5)],
    ] as [$asset, $event, $time]) {
        AnalyticsEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'fingerprint' => hash('sha256', Str::random()),
            'event_name' => $event,
            'subject_type' => $asset->getMorphClass(),
            'subject_id' => $asset->id,
            'session_id' => (string) Str::uuid(),
            'occurred_at' => $time,
        ]);
    }

    app(TrendingAssetService::class)->rebuild('week');

    expect(AssetTrendingScore::query()
        ->where('asset_id', $recent->id)
        ->where('period', 'week')
        ->value('rank'))
        ->toBe(1);
});

it('excludes suppressed assets and applies editorial boosts', function (): void {
    $boosted = createDiscoverableTrendingAsset([
        'trending_boost' => 25,
    ]);
    $suppressed = createDiscoverableTrendingAsset([
        'trending_boost' => 100,
        'suppress_from_trending' => true,
    ]);

    app(TrendingAssetService::class)->rebuild('now');

    expect(AssetTrendingScore::query()
        ->where('asset_id', $boosted->id)
        ->where('period', 'now')
        ->exists())
        ->toBeTrue()
        ->and(AssetTrendingScore::query()
            ->where('asset_id', $suppressed->id)
            ->exists())
        ->toBeFalse();
});
