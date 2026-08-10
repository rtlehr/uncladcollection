<?php

namespace App\Commerce\Pricing;

use App\Models\AssetOffering;

class DynamicLicensePriceCalculator
{
    public function calculate(AssetOffering $offering): array
    {
        $offering->loadMissing('licenseType');
        $license = $offering->licenseType;

        $imageUnits = max(0, (int) $offering->image_units);
        $videoUnits = max(0, (int) $offering->video_units);
        // price_cents remains a temporary compatibility fallback for older seeders/tests.
        $imageUnitPrice = (int) $license->image_unit_price_cents;
        $videoUnitPrice = (int) $license->video_unit_price_cents;
        if ($imageUnitPrice === 0 && $videoUnitPrice === 0 && (int) $license->price_cents > 0) {
            $imageUnitPrice = (int) $license->price_cents;
            $videoUnitPrice = (int) $license->price_cents;
        }

        $pricingModel = $license->pricing_model === 'flat_total' ? 'flat_total' : 'per_unit';
        $imageSubtotal = $pricingModel === 'per_unit' ? $imageUnits * $imageUnitPrice : 0;
        $videoSubtotal = $pricingModel === 'per_unit' ? $videoUnits * $videoUnitPrice : 0;

        $usesLegacyOfferingPrice = $pricingModel === 'per_unit'
            && $imageUnits === 0
            && $videoUnits === 0
            && (int) $offering->price_cents > 0;

        if ($pricingModel === 'flat_total') {
            $contentSubtotal = $license->total_price_cents !== null
                ? (int) $license->total_price_cents
                : max(0, (int) $license->price_cents);
        } else {
            $contentSubtotal = $usesLegacyOfferingPrice
                ? (int) $offering->price_cents
                : $imageSubtotal + $videoSubtotal;
        }

        $calculated = max(0, $contentSubtotal + (int) $offering->price_adjustment_cents);

        if ($license->minimum_price_cents !== null) {
            $calculated = max($calculated, (int) $license->minimum_price_cents);
        }

        $final = $offering->price_override_cents !== null
            ? (int) $offering->price_override_cents
            : $calculated;

        return [
            'version' => 3,
            'pricing_model' => $pricingModel,
            'total_price_cents' => $license->total_price_cents !== null ? (int) $license->total_price_cents : null,
            'image_units' => $imageUnits,
            'video_units' => $videoUnits,
            'image_unit_price_cents' => $imageUnitPrice,
            'video_unit_price_cents' => $videoUnitPrice,
            'image_subtotal_cents' => $imageSubtotal,
            'video_subtotal_cents' => $videoSubtotal,
            'legacy_offering_price_applied' => $usesLegacyOfferingPrice,
            'legacy_offering_price_cents' => $usesLegacyOfferingPrice ? (int) $offering->price_cents : null,
            'price_adjustment_cents' => (int) $offering->price_adjustment_cents,
            'minimum_price_cents' => $license->minimum_price_cents !== null ? (int) $license->minimum_price_cents : null,
            'price_override_cents' => $offering->price_override_cents !== null ? (int) $offering->price_override_cents : null,
            'calculated_price_cents' => $calculated,
            'final_price_cents' => max(0, $final),
            'currency' => strtoupper($offering->currency ?: $license->currency),
        ];
    }
}
