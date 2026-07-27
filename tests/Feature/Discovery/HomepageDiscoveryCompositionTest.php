<?php

use App\Models\HomepageDiscoverySection;
use App\Services\HomepageDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('orders enabled homepage sections and removes duplicate ranked assets', function (): void {
    HomepageDiscoverySection::query()->where('section_key', 'featured_assets')->update(['is_enabled' => false]);
    HomepageDiscoverySection::query()->where('section_key', 'primary_collections')->update(['is_enabled' => false]);
    HomepageDiscoverySection::query()->where('section_key', 'secondary_collections')->update(['is_enabled' => false]);

    $sections = app(HomepageDiscoveryService::class)->compose(true, [
        'recommended' => [['id' => 10], ['id' => 20]],
        'trending' => [['id' => 20], ['id' => 30]],
    ]);

    expect(array_column($sections, 'key'))->toBe(['recommended', 'trending'])
        ->and(array_column($sections[0]['items'], 'id'))->toBe([10, 20])
        ->and(array_column($sections[1]['items'], 'id'))->toBe([30]);
});

it('respects audience and enabled controls', function (): void {
    HomepageDiscoverySection::query()->update(['is_enabled' => false]);
    HomepageDiscoverySection::query()->where('section_key', 'trending')->update(['is_enabled' => true, 'audience' => 'guest']);

    $service = app(HomepageDiscoveryService::class);

    expect($service->compose(false, ['trending' => [['id' => 1]]]))->toHaveCount(1)
        ->and($service->compose(true, ['trending' => [['id' => 1]]]))->toBe([]);
});
