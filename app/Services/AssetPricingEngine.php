<?php

namespace App\Services;

use App\Commerce\Configuration\ConfigurationSelection;
use App\Commerce\Pricing\PricingEngine;
use App\Data\AssetPricingQuote;
use App\Models\AssetOffering;

/**
 * @deprecated Use App\Commerce\Pricing\PricingEngine for new code.
 */
class AssetPricingEngine
{
    public function __construct(
        private readonly PricingEngine $engine,
    ) {}

    public function quote(
        AssetOffering $offering,
        array $selections,
        int $quantity = 1,
        ?int $aggregateQuantity = null,
    ): AssetPricingQuote {
        $breakdown = $this->engine->quote(
            $offering,
            ConfigurationSelection::fromNormalizedValues($selections),
            $quantity,
            $aggregateQuantity,
        );

        return new AssetPricingQuote(
            quantity: $breakdown->quantity,
            aggregateQuantity: $breakdown->aggregateQuantity,
            baseUnitPriceCents: $breakdown->baseUnitPriceCents,
            configurationAdjustmentCents: $breakdown->configurationAdjustmentCents,
            configuredUnitPriceCents: $breakdown->configuredUnitPriceCents,
            finalUnitPriceCents: $breakdown->finalUnitPriceCents,
            lineTotalCents: $breakdown->lineTotalCents,
            pricingTierId: $breakdown->pricingTierId,
            pricingTierLabel: $breakdown->pricingTierLabel,
            nextTierMinimumQuantity: $breakdown->nextTierMinimumQuantity,
            unitsUntilNextTier: $breakdown->unitsUntilNextTier,
            currency: $breakdown->currency,
            tierDiscountCents: $breakdown->tierDiscountCents,
        );
    }

    public function configurationHash(array $selections): string
    {
        return ConfigurationSelection::fromNormalizedValues($selections)->hash();
    }
}
