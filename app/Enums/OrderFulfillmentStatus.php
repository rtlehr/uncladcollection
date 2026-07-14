<?php

namespace App\Enums;

enum OrderFulfillmentStatus: string
{
    case New = 'new';
    case Processing = 'processing';
    case ReadyToPackage = 'ready_to_package';
    case Packaged = 'packaged';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Fulfilled = 'fulfilled';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Processing => 'Processing',
            self::ReadyToPackage => 'Ready to Package',
            self::Packaged => 'Packaged',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Fulfilled => 'Fulfilled',
            self::Canceled => 'Canceled',
        };
    }
}
