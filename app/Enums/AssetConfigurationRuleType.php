<?php

namespace App\Enums;

enum AssetConfigurationRuleType: string
{
    case FixedAdjustment = 'fixed_adjustment';
    case PercentageAdjustment = 'percentage_adjustment';
}
