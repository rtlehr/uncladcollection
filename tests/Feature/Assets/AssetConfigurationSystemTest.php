<?php

use App\Models\Asset;
use App\Models\AssetConfigurationGroup;
use App\Services\AssetConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    assertDedicatedTestDatabase();
});

it('stores configurable product groups, values, and fixed price rules', function (): void {
    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(), 'title' => 'Configurable Poster', 'slug' => 'configurable-poster',
        'asset_type' => 'image', 'status' => 'draft', 'sort_order' => 0, 'is_active' => true,
    ]);

    app(AssetConfigurationService::class)->saveMany($asset, [[
        'name' => 'Size', 'display_type' => 'select', 'is_required' => true, 'is_active' => true,
        'values' => [
            ['label' => 'Small', 'value' => 'small', 'is_active' => true, 'price_adjustment_cents' => 0],
            ['label' => 'Large', 'value' => 'large', 'is_active' => true, 'price_adjustment_cents' => 500],
        ],
    ]]);

    $group = $asset->configurationGroups()->with('values.rules')->firstOrFail();
    expect($group->name)->toBe('Size')
        ->and($group->is_required)->toBeTrue()
        ->and($group->values)->toHaveCount(2)
        ->and($group->values->last()->rules->first()->amount_cents)->toBe(500);
});

it('replaces prior configuration safely when the builder is saved again', function (): void {
    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(), 'title' => 'Configurable Video', 'slug' => 'configurable-video',
        'asset_type' => 'video', 'status' => 'draft', 'sort_order' => 0, 'is_active' => true,
    ]);
    $service = app(AssetConfigurationService::class);
    $service->saveMany($asset, [['name' => 'Resolution', 'display_type' => 'radio', 'values' => [['label' => '4K', 'price_adjustment_cents' => 1000]]]]);
    $service->saveMany($asset, [['name' => 'Language', 'display_type' => 'select', 'values' => [['label' => 'English', 'price_adjustment_cents' => 0]]]]);

    expect($asset->configurationGroups()->count())->toBe(1)
        ->and($asset->configurationGroups()->first()->name)->toBe('Language')
        ->and(AssetConfigurationGroup::withTrashed()->count())->toBe(1);
});
