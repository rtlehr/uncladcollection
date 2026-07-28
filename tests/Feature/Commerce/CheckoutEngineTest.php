<?php

use App\Commerce\Cart\CartEngine;
use App\Commerce\Checkout\CheckoutEngine;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\OrderFulfillmentStatus;
use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\CartItem;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function checkoutEngineFixture(): array
{
    assertDedicatedTestDatabase();

    $user = User::query()->create([
        'name' => 'Checkout Tester',
        'email' => Str::uuid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $licenseType = LicenseType::query()->create([
        'name' => 'Configured Product License',
        'slug' => 'configured-product-'.Str::lower(Str::random(8)),
        'description' => 'A test checkout license.',
        'price_cents' => 2500,
        'currency' => 'USD',
        'download_limit' => 5,
        'usage_terms' => 'Testing terms.',
        'is_active' => true,
        'sort_order' => 10,
    ]);

    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Configured Checkout Product',
        'slug' => 'configured-checkout-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::Product,
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now()->subMinute(),
    ]);

    $offering = AssetOffering::query()->create([
        'asset_id' => $asset->id,
        'license_type_id' => $licenseType->id,
        'name' => 'Standard Product',
        'description' => 'Standard configured product.',
        'price_cents' => 2500,
        'currency' => 'USD',
        'download_limit' => 5,
        'is_active' => true,
        'sort_order' => 10,
    ]);

    app(CartEngine::class)->addAssetLines($user, $offering, [
        ['quantity' => 2, 'selections' => []],
    ]);

    return [$user, $asset, $offering];
}

it('creates a pending order snapshot from configured asset cart lines', function () {
    [$user, $asset, $offering] = checkoutEngineFixture();

    $cartItems = CartItem::query()->where('user_id', $user->id)->get();
    $plan = app(CheckoutEngine::class)->prepareCartCheckout($user, $cartItems);

    expect($plan->order->status)->toBe(Order::STATUS_PENDING)
        ->and($plan->order->fulfillment_status)->toBe(OrderFulfillmentStatus::New)
        ->and($plan->order->commerce_version)->toBe('2.0')
        ->and($plan->order->total_cents)->toBe(5000)
        ->and($plan->order->checkout_snapshot)->toBeArray()
        ->and($plan->lineItems)->toHaveCount(1)
        ->and(data_get($plan->lineItems, '0.quantity'))->toBe(2)
        ->and(data_get($plan->lineItems, '0.price_data.unit_amount'))->toBe(2500)
        ->and($plan->metadata['order_id'])->toBe((string) $plan->order->id);

    $item = $plan->order->items()->firstOrFail();

    expect($item->asset_id)->toBe($asset->id)
        ->and($item->asset_offering_id)->toBe($offering->id)
        ->and($item->image_id)->toBeNull()
        ->and($item->quantity)->toBe(2)
        ->and($item->asset_title)->toBe($asset->title)
        ->and($item->configuration_snapshot)->toBeArray()
        ->and($item->pricing_snapshot)->toBeArray();
});

it('finalizes a configured asset order idempotently and creates a license', function () {
    [$user, $asset] = checkoutEngineFixture();

    $plan = app(CheckoutEngine::class)->prepareCartCheckout(
        $user,
        CartItem::query()->where('user_id', $user->id)->get(),
    );

    $first = app(CheckoutEngine::class)->markPaid($plan->order);
    $second = app(CheckoutEngine::class)->markPaid($plan->order);

    expect($first->status)->toBe(Order::STATUS_PAID)
        ->and($first->fulfillment_status)->toBe(OrderFulfillmentStatus::ReadyToPackage)
        ->and($second->status)->toBe(Order::STATUS_PAID)
        ->and(License::query()->where('order_id', $plan->order->id)->count())->toBe(1)
        ->and(CartItem::query()->where('user_id', $user->id)->count())->toBe(0);

    $license = License::query()->where('order_id', $plan->order->id)->firstOrFail();

    expect($license->asset_id)->toBe($asset->id)
        ->and($license->image_id)->toBeNull()
        ->and($license->commerce_version)->toBe('2.0')
        ->and($license->configuration_snapshot)->toBeArray()
        ->and($license->pricing_snapshot)->toBeArray();
});
