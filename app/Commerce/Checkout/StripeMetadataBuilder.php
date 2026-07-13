<?php

namespace App\Commerce\Checkout;

use App\Models\Order;

final class StripeMetadataBuilder
{
    /** @return array<string, string> */
    public function forOrder(Order $order, string $checkoutType): array
    {
        return [
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'user_id' => (string) $order->user_id,
            'checkout_type' => $checkoutType,
            'commerce_version' => (string) ($order->commerce_version ?: '2.0'),
        ];
    }
}
