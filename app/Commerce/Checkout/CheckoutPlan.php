<?php

namespace App\Commerce\Checkout;

use App\Models\Order;

final class CheckoutPlan
{
    /**
     * @param array<int, array<string, mixed>> $lineItems
     * @param array<string, string> $metadata
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $lineItems,
        public readonly array $metadata,
        public readonly string $currency,
        public readonly int $totalCents,
    ) {}
}
