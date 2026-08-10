<?php

use App\Commerce\Configuration\ConfigurationSelection;
use App\Commerce\Pricing\DynamicLicensePriceCalculator;
use App\Commerce\Pricing\PricingEngine;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function dynamicPricingFixture(array $license = [], array $offering = []): AssetOffering
{
    $asset = Asset::factory()->create();
    $licenseType = LicenseType::query()->create(array_merge([
        'name' => 'Commercial '.Str::upper(Str::random(6)),
        'slug' => 'commercial-'.Str::lower(Str::random(10)), 'description' => 'Test',
        'price_cents' => 100, 'image_unit_price_cents' => 100,
        'video_unit_price_cents' => 500, 'currency' => 'USD',
        'is_active' => true, 'sort_order' => 10,
    ], $license));

    return AssetOffering::query()->create(array_merge([
        'asset_id' => $asset->id, 'license_type_id' => $licenseType->id,
        'name' => 'Package', 'image_units' => 3, 'video_units' => 1,
        'price_cents' => 0, 'price_adjustment_cents' => 0,
        'currency' => 'USD', 'is_active' => true, 'sort_order' => 10,
    ], $offering));
}

it('calculates a package from image and video units', function (): void {
    $price = app(DynamicLicensePriceCalculator::class)->calculate(dynamicPricingFixture());

    expect($price['image_subtotal_cents'])->toBe(300)
        ->and($price['video_subtotal_cents'])->toBe(500)
        ->and($price['final_price_cents'])->toBe(800);
});

it('applies minimum price adjustment and override in the correct order', function (): void {
    $offering = dynamicPricingFixture(
        ['minimum_price_cents' => 1000],
        ['image_units' => 1, 'video_units' => 0, 'price_adjustment_cents' => 50],
    );
    expect(app(DynamicLicensePriceCalculator::class)->calculate($offering)['final_price_cents'])->toBe(1000);

    $offering->update(['price_override_cents' => 750]);
    expect(app(DynamicLicensePriceCalculator::class)->calculate($offering->fresh())['final_price_cents'])->toBe(750);
});

it('uses dynamic pricing as the base for tiers and cart quantities', function (): void {
    $offering = dynamicPricingFixture();
    $quote = app(PricingEngine::class)->quote($offering, ConfigurationSelection::fromNormalizedValues([]), quantity: 2);

    expect($quote->baseUnitPriceCents)->toBe(800)
        ->and($quote->lineTotalCents)->toBe(1600)
        ->and($quote->toArray()['version'])->toBe(1);
});


it('supports a flat total license price regardless of image count', function (): void {
    $offering = dynamicPricingFixture(
        [
            'pricing_model' => 'flat_total',
            'total_price_cents' => 500,
            'image_unit_price_cents' => 100,
            'video_unit_price_cents' => 500,
        ],
        ['image_units' => 10, 'video_units' => 0],
    );

    $price = app(DynamicLicensePriceCalculator::class)->calculate($offering);

    expect($price['pricing_model'])->toBe('flat_total')
        ->and($price['image_units'])->toBe(10)
        ->and($price['image_subtotal_cents'])->toBe(0)
        ->and($price['final_price_cents'])->toBe(500);
});

it('charges the configured price for every image unit', function (): void {
    $offering = dynamicPricingFixture(
        ['pricing_model' => 'per_unit', 'image_unit_price_cents' => 100, 'video_unit_price_cents' => 0],
        ['image_units' => 5, 'video_units' => 0],
    );

    $price = app(DynamicLicensePriceCalculator::class)->calculate($offering);

    expect($price['pricing_model'])->toBe('per_unit')
        ->and($price['image_subtotal_cents'])->toBe(500)
        ->and($price['final_price_cents'])->toBe(500);
});
