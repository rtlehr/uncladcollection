<?php

namespace App\Enums;

enum AssetFulfillmentType: string
{
    case Digital = 'digital';
    case Physical = 'physical';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Digital => 'Digital',
            self::Physical => 'Physical',
            self::Hybrid => 'Hybrid',
        };
    }

    public function requiresShipment(): bool
    {
        return $this !== self::Digital;
    }
}
