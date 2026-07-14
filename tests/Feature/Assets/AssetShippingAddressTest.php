<?php

use App\Commerce\Cart\CartEngine;
use App\Commerce\Fulfillment\ShippingAddress;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a shipping address snapshot on a configured cart line', function () {
    $user = User::factory()->create();
    $license = LicenseType::query()->create(['name' => 'Product', 'slug' => 'product', 'description' => null, 'price_cents' => 2000, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 1]);
    $asset = Asset::query()->create([
        'uuid' => (string) str()->uuid(), 'title' => 'Shirt', 'slug' => 'shirt',
        'asset_type' => AssetType::Product, 'status' => AssetStatus::Published,
        'sort_order' => 1, 'is_active' => true, 'allows_quantity' => true,
        'fulfillment_type' => 'physical', 'collects_shipping_address' => true,
        'shipping_address_required' => true,
    ]);
    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id, 'license_type_id' => $license->id, 'name' => 'Shirt',
        'price_cents' => 2000, 'currency' => 'USD', 'is_active' => true, 'sort_order' => 1,
    ]);
    $address = ShippingAddress::fromInput([
        'full_name' => 'Ross Lehr', 'address_line_1' => '123 Main St', 'city' => 'Richmond',
        'region' => 'VA', 'postal_code' => '23219', 'country_code' => 'US',
    ]);

    app(CartEngine::class)->addAssetLines($user, $offering, [['quantity' => 2, 'selections' => []]], $address);

    $item = \App\Models\CartItem::query()
    ->where('user_id', $user->id)
    ->latest('id')
    ->firstOrFail();
    expect($item->shipping_address_snapshot['full_name'])->toBe('Ross Lehr')
        ->and($item->shipping_address_hash)->toBe($address->hash());
});
