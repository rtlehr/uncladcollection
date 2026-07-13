<?php

namespace App\Enums;

enum AssetPricingTierType: string
{
    case FixedUnitPrice = 'fixed_unit_price';
    case PercentageOff = 'percentage_off';

    public function label(): string
    {
        return match ($this) {
            self::FixedUnitPrice => 'Fixed unit price',
            self::PercentageOff => 'Percentage off',
        };
    }
}
