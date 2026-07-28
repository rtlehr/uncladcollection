<?php

use App\Commerce\Configuration\ConfigurationSelection;
use App\Commerce\Pricing\PricingEngine;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\AssetPricingTier;
use App\Models\LicenseType;
use App\Services\AssetPricingEngine as LegacyPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    assertDedicatedTestDatabase();
});

it('normalizes configuration selections deterministically', function (): void {
    $first = ConfigurationSelection::fromNormalizedValues([
        'color' => 'black',
        'size' => ['large', 'small'],
    ]);

    $second = ConfigurationSelection::fromNormalizedValues([
        'size' => ['small', 'large'],
        'color' => 'black',
    ]);

    expect($first->hash())->toBe($second->hash())
        ->and($first->equals($second))->toBeTrue()
        ->and($first->toSnapshotArray()['version'])->toBe(1);
});

it('keeps the legacy pricing service compatible with the new pricing engine', function (): void {
    $licenseType = LicenseType::query()->create([
        'name' => 'Commerce Test',
        'slug' => 'commerce-test-'.uniqid(),
        'description' => 'Commerce architecture test license.',
        'terms' => 'Test terms.',
        'price_cents' => 2500,
        'currency' => 'USD',
        'download_limit' => 5,
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $asset = Asset::query()->create([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'slug' => 'commerce-architecture-'.uniqid(),
        'title' => 'Commerce Architecture',
        'asset_type' => 'product',
        'status' => 'published',
        'is_active' => true,
        'published_at' => now(),
    ]);

    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id,
        'license_type_id' => $licenseType->id,
        'name' => 'Configured Product',
        'price_cents' => 2500,
        'currency' => 'USD',
        'download_limit' => 5,
        'is_active' => true,
        'sort_order' => 10,
    ]);

    AssetPricingTier::query()->create([
        'asset_id' => $asset->id,
        'asset_offering_id' => $offering->id,
        'minimum_quantity' => 5,
        'maximum_quantity' => null,
        'pricing_type' => 'percentage_off',
        'percentage_off' => 10,
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $new = app(PricingEngine::class)->quote(
        $offering,
        ConfigurationSelection::fromNormalizedValues([]),
        quantity: 2,
        aggregateQuantity: 5,
    );

    $legacy = app(LegacyPricingEngine::class)->quote(
        $offering,
        [],
        quantity: 2,
        aggregateQuantity: 5,
    );

    expect($new->finalUnitPriceCents)->toBe(2250)
        ->and($new->tierDiscountCents)->toBe(250)
        ->and($legacy->toArray())->toBe($new->toArray());
});
