<?php

use App\Enums\OrderFulfillmentStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tracks fulfillment status changes and shipment details', function () {
    $user = User::factory()->create();
    $order = Order::query()->create([
        'user_id'=>$user->id,'status'=>Order::STATUS_PAID,'fulfillment_status'=>'new',
        'subtotal_cents'=>2500,'discount_cents'=>0,'tax_cents'=>0,'total_cents'=>2500,'currency'=>'USD',
        'payment_provider'=>Order::PAYMENT_PROVIDER_STRIPE,
    ]);

    app(OrderFulfillmentService::class)->update($order,[
        'fulfillment_status'=>OrderFulfillmentStatus::Shipped->value,
        'shipping_carrier'=>'USPS','tracking_number'=>'9400TEST','event_note'=>'Package left the studio.',
    ],$user);

    $order->refresh();
    expect($order->fulfillment_status)->toBe(OrderFulfillmentStatus::Shipped)
        ->and($order->tracking_number)->toBe('9400TEST')
        ->and($order->shipped_at)->not->toBeNull()
        ->and($order->fulfillmentEvents()->count())->toBe(1);
});
