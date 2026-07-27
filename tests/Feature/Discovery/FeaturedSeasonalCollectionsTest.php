<?php

use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Collection;
use App\Models\DiscoveryCollectionPlacement;
use App\Services\DiscoveryCollectionPlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPlacementCollection(string $name = 'Summer Outdoors'): Collection
{
    $collection = Collection::query()->create([
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'description' => 'A scheduled collection for discovery testing.',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $asset = Asset::factory()->published()->create([
        'collection_id' => $collection->id,
    ]);
    AssetFile::factory()->preview()->for($asset)->create();

    return $collection;
}

it('returns active scheduled collection placements for the homepage', function (): void {
    $collection = createPlacementCollection();

    DiscoveryCollectionPlacement::query()->create([
        'collection_id' => $collection->id,
        'placement' => 'homepage_primary',
        'content_type' => 'seasonal',
        'audience' => 'all',
        'heading' => 'Summer starts here',
        'sort_order' => 0,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'is_active' => true,
    ]);

    $placements = app(DiscoveryCollectionPlacementService::class)->homepage(false);

    expect($placements['homepage_primary'])->toHaveCount(1)
        ->and($placements['homepage_primary'][0]['heading'])->toBe('Summer starts here')
        ->and($placements['homepage_primary'][0]['source'])->toBe('seasonal_collection')
        ->and($placements['homepage_primary'][0]['collection']['assets_count'])->toBe(1);
});

it('excludes future expired inactive and mismatched audience placements', function (): void {
    $collection = createPlacementCollection('Audience Collection');

    foreach ([
        ['starts_at' => now()->addDay(), 'ends_at' => null, 'is_active' => true, 'audience' => 'all'],
        ['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(), 'is_active' => true, 'audience' => 'all'],
        ['starts_at' => null, 'ends_at' => null, 'is_active' => false, 'audience' => 'all'],
        ['starts_at' => null, 'ends_at' => null, 'is_active' => true, 'audience' => 'authenticated'],
    ] as $index => $attributes) {
        DiscoveryCollectionPlacement::query()->create([
            'collection_id' => $collection->id,
            'placement' => 'homepage_secondary',
            'content_type' => 'featured',
            'audience' => $attributes['audience'],
            'heading' => 'Placement '.$index,
            'sort_order' => $index,
            'starts_at' => $attributes['starts_at'],
            'ends_at' => $attributes['ends_at'],
            'is_active' => $attributes['is_active'],
        ]);
    }

    expect(app(DiscoveryCollectionPlacementService::class)->homepage(false))->toBe([]);
});
