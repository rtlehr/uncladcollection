<?php

namespace App\Data;

use App\Commerce\Pricing\PriceBreakdown;

/**
 * @deprecated Use App\Commerce\Pricing\PriceBreakdown for new code.
 */
class AssetPricingQuote extends PriceBreakdown
{
    public function __construct(
        int $quantity,
        int $aggregateQuantity,
        int $baseUnitPriceCents,
        int $configurationAdjustmentCents,
        int $configuredUnitPriceCents,
        int $finalUnitPriceCents,
        int $lineTotalCents,
        ?int $pricingTierId,
        ?string $pricingTierLabel,
        ?int $nextTierMinimumQuantity,
        ?int $unitsUntilNextTier,
        string $currency,
        ?int $tierDiscountCents = null,
    ) {
        parent::__construct(
            quantity: $quantity,
            aggregateQuantity: $aggregateQuantity,
            baseUnitPriceCents: $baseUnitPriceCents,
            configurationAdjustmentCents: $configurationAdjustmentCents,
            configuredUnitPriceCents: $configuredUnitPriceCents,
            tierDiscountCents: $tierDiscountCents ?? max(0, $configuredUnitPriceCents - $finalUnitPriceCents),
            finalUnitPriceCents: $finalUnitPriceCents,
            lineTotalCents: $lineTotalCents,
            pricingTierId: $pricingTierId,
            pricingTierLabel: $pricingTierLabel,
            nextTierMinimumQuantity: $nextTierMinimumQuantity,
            unitsUntilNextTier: $unitsUntilNextTier,
            currency: $currency,
        );
    }
}
