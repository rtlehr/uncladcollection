<?php

namespace App\Commerce\Events;

use App\Commerce\Pricing\PriceBreakdown;

final class PriceCalculated
{
    public function __construct(
        public readonly int $offeringId,
        public readonly PriceBreakdown $breakdown,
    ) {}
}
