<?php

namespace App\Commerce\Events;

final class CartLineMerged
{
    public function __construct(
        public readonly int $cartItemId,
        public readonly int $previousQuantity,
        public readonly int $newQuantity,
    ) {}
}
