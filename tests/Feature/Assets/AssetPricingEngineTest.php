<?php

use App\Enums\AssetPricingTierType;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use App\Services\AssetPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(config('database.default'))->toBe('mysql');
    expect(config('database.connections.mysql.database'))->toBe('uncladcollection_testing');
});

it('applies a percentage quantity tier after configuration adjustments', function (): void {
    $asset = Asset::query()->create([
        'uuid' => (string) str()->uuid(), 'title' => 'Configured Product', 'slug' => 'configured-product',
        'asset_type' => 'product', 'status' => 'published', 'is_active' => true, 'sort_order' => 0,
    ]);
    $license = LicenseType::query()->create([
        'name' => 'Product Purchase', 'slug' => 'product-purchase', 'description' => 'Test',
        'price_cents' => 2000, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 0,
    ]);
    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id, 'license_type_id' => $license->id, 'name' => 'Standard',
        'price_cents' => 2000, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 10,
    ]);
    $asset->pricingTiers()->create([
        'asset_offering_id' => $offering->id, 'minimum_quantity' => 5, 'maximum_quantity' => 9,
        'pricing_type' => AssetPricingTierType::PercentageOff, 'percentage_off' => 10,
        'currency' => 'USD', 'is_active' => true,
    ]);

    $quote = app(AssetPricingEngine::class)->quote($offering, [], quantity: 2, aggregateQuantity: 5);

    expect($quote->finalUnitPriceCents)->toBe(1800)
        ->and($quote->lineTotalCents)->toBe(3600)
        ->and($quote->aggregateQuantity)->toBe(5)
        ->and($quote->pricingTierId)->not->toBeNull();
});

it('uses the combined asset and offering quantity to unlock a tier', function (): void {
    $asset = Asset::query()->create([
        'uuid' => (string) str()->uuid(), 'title' => 'Bulk Product', 'slug' => 'bulk-product',
        'asset_type' => 'product', 'status' => 'published', 'is_active' => true, 'sort_order' => 0,
    ]);
    $license = LicenseType::query()->create([
        'name' => 'Bulk Purchase', 'slug' => 'bulk-purchase', 'description' => 'Test',
        'price_cents' => 2500, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 0,
    ]);
    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id, 'license_type_id' => $license->id, 'name' => 'Standard',
        'price_cents' => 2500, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 10,
    ]);
    $asset->pricingTiers()->create([
        'asset_offering_id' => $offering->id, 'minimum_quantity' => 10,
        'pricing_type' => AssetPricingTierType::FixedUnitPrice, 'unit_price_cents' => 1900,
        'currency' => 'USD', 'is_active' => true,
    ]);

    $quote = app(AssetPricingEngine::class)->quote($offering, [], quantity: 3, aggregateQuantity: 10);

    expect($quote->finalUnitPriceCents)->toBe(1900)
        ->and($quote->lineTotalCents)->toBe(5700);
});

it('generates the same merge hash for selections in different key order', function (): void {
    $engine = app(AssetPricingEngine::class);

    expect($engine->configurationHash(['size' => 'large', 'color' => 'black']))
        ->toBe($engine->configurationHash(['color' => 'black', 'size' => 'large']));
});
