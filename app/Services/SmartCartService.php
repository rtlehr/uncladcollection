<?php

namespace App\Services;

use App\Commerce\Cart\CartEngine;
use App\Models\AssetOffering;
use App\Models\CartItem;
use App\Models\User;

/**
 * @deprecated Use App\Commerce\Cart\CartEngine for new code.
 */
class SmartCartService
{
    public function __construct(
        private readonly CartEngine $engine,
    ) {}

    public function addAssetLines(User $user, AssetOffering $offering, array $lines): void
    {
        $this->engine->addAssetLines($user, $offering, $lines);
    }

    public function updateAssetQuantity(CartItem $cartItem, int $quantity): void
    {
        $this->engine->updateAssetQuantity($cartItem, $quantity);
    }

    public function remove(CartItem $cartItem): void
    {
        $this->engine->remove($cartItem);
    }
}
