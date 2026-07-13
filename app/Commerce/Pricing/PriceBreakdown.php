<?php

namespace App\Commerce\Pricing;

class PriceBreakdown
{
    public function __construct(
        public readonly int $quantity,
        public readonly int $aggregateQuantity,
        public readonly int $baseUnitPriceCents,
        public readonly int $configurationAdjustmentCents,
        public readonly int $configuredUnitPriceCents,
        public readonly int $tierDiscountCents,
        public readonly int $finalUnitPriceCents,
        public readonly int $lineTotalCents,
        public readonly ?int $pricingTierId,
        public readonly ?string $pricingTierLabel,
        public readonly ?int $nextTierMinimumQuantity,
        public readonly ?int $unitsUntilNextTier,
        public readonly string $currency,
    ) {}

    public function toArray(): array
    {
        return [
            'version' => 1,
            'quantity' => $this->quantity,
            'aggregate_quantity' => $this->aggregateQuantity,
            'base_unit_price_cents' => $this->baseUnitPriceCents,
            'configuration_adjustment_cents' => $this->configurationAdjustmentCents,
            'configured_unit_price_cents' => $this->configuredUnitPriceCents,
            'tier_discount_cents' => $this->tierDiscountCents,
            'final_unit_price_cents' => $this->finalUnitPriceCents,
            'line_total_cents' => $this->lineTotalCents,
            'pricing_tier_id' => $this->pricingTierId,
            'pricing_tier_label' => $this->pricingTierLabel,
            'next_tier_minimum_quantity' => $this->nextTierMinimumQuantity,
            'units_until_next_tier' => $this->unitsUntilNextTier,
            'currency' => $this->currency,
        ];
    }
}
