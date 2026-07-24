<?php

use App\Commerce\Pricing\DynamicLicensePriceCalculator;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use Database\Seeders\LicenseTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds explicit image and video unit prices', function (): void {
    $this->seed(LicenseTypeSeeder::class);

    $personal = LicenseType::query()->where('slug', 'personal-use')->firstOrFail();

    expect($personal->price_cents)->toBe(100)
        ->and($personal->image_unit_price_cents)->toBe(100)
        ->and($personal->video_unit_price_cents)->toBe(500)
        ->and($personal->minimum_price_cents)->toBe(100);
});

it('creates dynamically priced offerings from factories', function (): void {
    $offering = AssetOffering::factory()->create([
        'image_units' => 3,
        'video_units' => 1,
    ]);

    $price = app(DynamicLicensePriceCalculator::class)->calculate($offering);

    expect($price['final_price_cents'])->toBe(6000);
});

it('keeps a factory state for legacy fixed-price compatibility', function (): void {
    $offering = AssetOffering::factory()->legacyFixedPrice()->create();

    $price = app(DynamicLicensePriceCalculator::class)->calculate($offering);

    expect($price['final_price_cents'])->toBe(2000);
});
