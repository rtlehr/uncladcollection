<?php

namespace App\Commerce\Pricing;

use App\Commerce\Configuration\ConfigurationManager;
use App\Commerce\Configuration\ConfigurationSelection;
use App\Enums\AssetPricingTierType;
use App\Models\AssetOffering;
use App\Models\AssetPricingTier;
use App\Services\AssetPricingService;

class PricingEngine
{
    public function __construct(
        private readonly ConfigurationManager $configurationManager,
        private readonly AssetPricingService $pricingService,
        private readonly DynamicLicensePriceCalculator $dynamicPriceCalculator,
    ) {}

    public function quote(
        AssetOffering $offering,
        ConfigurationSelection $selection,
        int $quantity = 1,
        ?int $aggregateQuantity = null,
    ): PriceBreakdown {
        $quantity = max(1, $quantity);
        $aggregateQuantity = max($quantity, $aggregateQuantity ?? $quantity);
        $offering->loadMissing('asset.configurationGroups.values.rules');

        $adjustment = $this->configurationManager->calculateAdjustment(
            $offering->asset->configurationGroups,
            $selection,
            $offering->id,
        );

        $dynamicPrice = $this->dynamicPriceCalculator->calculate($offering);
        $basePrice = (int) $dynamicPrice['final_price_cents'];
        $configuredUnit = max(0, $basePrice + $adjustment);
        $tiers = $this->pricingService->activeForOffering($offering->asset, $offering);

        /** @var AssetPricingTier|null $tier */
        $tier = $tiers->first(
            fn (AssetPricingTier $candidate) => $candidate->appliesToQuantity($aggregateQuantity),
        );

        $finalUnit = $this->applyTier($configuredUnit, $tier);
        $nextTier = $tiers->first(
            fn (AssetPricingTier $candidate) => $candidate->minimum_quantity > $aggregateQuantity,
        );

        return new PriceBreakdown(
            quantity: $quantity,
            aggregateQuantity: $aggregateQuantity,
            baseUnitPriceCents: $basePrice,
            configurationAdjustmentCents: $adjustment,
            configuredUnitPriceCents: $configuredUnit,
            tierDiscountCents: max(0, $configuredUnit - $finalUnit),
            finalUnitPriceCents: $finalUnit,
            lineTotalCents: $finalUnit * $quantity,
            pricingTierId: $tier?->id,
            pricingTierLabel: $tier ? $this->tierLabel($tier) : null,
            nextTierMinimumQuantity: $nextTier?->minimum_quantity,
            unitsUntilNextTier: $nextTier
                ? max(0, $nextTier->minimum_quantity - $aggregateQuantity)
                : null,
            currency: strtoupper($offering->currency),
        );
    }

    private function applyTier(int $configuredUnit, ?AssetPricingTier $tier): int
    {
        if (! $tier) {
            return $configuredUnit;
        }

        return match ($tier->pricing_type) {
            AssetPricingTierType::FixedUnitPrice => min($configuredUnit, (int) $tier->unit_price_cents),
            AssetPricingTierType::PercentageOff => max(
                0,
                (int) round($configuredUnit * (1 - ((float) $tier->percentage_off / 100))),
            ),
        };
    }

    private function tierLabel(AssetPricingTier $tier): string
    {
        return match ($tier->pricing_type) {
            AssetPricingTierType::FixedUnitPrice => '$'.number_format(((int) $tier->unit_price_cents) / 100, 2).' each',
            AssetPricingTierType::PercentageOff => rtrim(
                rtrim(number_format((float) $tier->percentage_off, 2), '0'),
                '.',
            ).'% off',
        };
    }
}
