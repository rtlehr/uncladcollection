<?php

use App\Enums\AssetPricingTierType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\AssetPricingTier;
use App\Models\CartItem;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\SmartCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function intelligentCartFixture(): array
{
    $user = User::query()->create([
        'name' => 'Cart Tester',
        'email' => Str::uuid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $license = LicenseType::query()->create([
        'name' => 'Product License',
        'slug' => 'product-license-'.Str::lower(Str::random(6)),
        'price_cents' => 2000,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Configurable Product',
        'slug' => 'configurable-product-'.Str::lower(Str::random(6)),
        'asset_type' => AssetType::Product,
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now()->subMinute(),
    ]);

    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id,
        'license_type_id' => $license->id,
        'name' => 'Standard Product',
        'price_cents' => 2000,
        'currency' => 'USD',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    return [$user, $asset, $offering];
}

it('merges identical configured lines', function () {
    [$user, , $offering] = intelligentCartFixture();

    app(SmartCartService::class)->addAssetLines($user, $offering, [
        ['quantity' => 2, 'selections' => []],
        ['quantity' => 3, 'selections' => []],
    ]);

    $item = CartItem::query()->where('user_id', $user->id)->firstOrFail();

    expect(CartItem::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($item->quantity)->toBe(5)
        ->and($item->line_total_cents)->toBe(10000);
});

it('uses aggregate quantity to unlock a pricing tier', function () {
    [$user, $asset, $offering] = intelligentCartFixture();

    AssetPricingTier::query()->create([
        'asset_id' => $asset->id,
        'asset_offering_id' => $offering->id,
        'minimum_quantity' => 5,
        'maximum_quantity' => null,
        'pricing_type' => AssetPricingTierType::PercentageOff,
        'percentage_off' => 10,
        'currency' => 'USD',
        'sort_order' => 10,
        'is_active' => true,
    ]);

    app(SmartCartService::class)->addAssetLines($user, $offering, [
        ['quantity' => 5, 'selections' => []],
    ]);

    $item = CartItem::query()->where('user_id', $user->id)->firstOrFail();

    expect($item->final_unit_price_cents)->toBe(1800)
        ->and($item->line_total_cents)->toBe(9000)
        ->and(data_get($item->pricing_snapshot, 'aggregate_quantity'))->toBe(5);
});
