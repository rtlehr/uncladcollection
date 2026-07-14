<?php

namespace App\Commerce\Cart;

use App\Commerce\Configuration\ConfigurationSelection;
use App\Commerce\Fulfillment\ShippingAddress;
use App\Commerce\Pricing\PricingEngine;
use App\Models\AssetOffering;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartEngine
{
    public function __construct(
        private readonly PricingEngine $pricingEngine,
        private readonly CartSnapshotFactory $snapshots,
    ) {}

    /**
     * @param array<int, array{quantity:int,selections?:array<string,mixed>}> $lines
     */
    public function addAssetLines(User $user, AssetOffering $offering, array $lines, ?ShippingAddress $shippingAddress = null): void
    {
        $offering->loadMissing([
            'asset.configurationGroups.values.rules',
            'pricingTiers',
            'licenseType',
        ]);

        abort_unless($offering->is_active && $offering->asset?->is_active, 404);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least one configured item.',
            ]);
        }

        DB::transaction(function () use ($user, $offering, $lines, $shippingAddress): void {
            foreach ($lines as $index => $line) {
                $quantity = max(1, min(999, (int) ($line['quantity'] ?? 1)));
                $selection = ConfigurationSelection::fromInput(
                    $offering->asset->configurationGroups,
                    (array) ($line['selections'] ?? []),
                    "lines.$index.selections",
                );

                $item = CartItem::query()->firstOrNew([
                    'user_id' => $user->id,
                    'asset_id' => $offering->asset_id,
                    'asset_offering_id' => $offering->id,
                    'configuration_hash' => $selection->hash(),
                    'shipping_address_hash' => $shippingAddress?->hash(),
                ]);

                $newQuantity = ($item->exists ? (int) $item->quantity : 0) + $quantity;
                $provisionalLineTotal = max(0, (int) $offering->price_cents) * $newQuantity;

                $item->fill([
                    'image_id' => null,
                    'license_type_id' => $offering->license_type_id,
                    'quantity' => $newQuantity,
                    'configuration_snapshot' => $this->snapshots->configuration($selection),
                    'shipping_address_snapshot' => $this->snapshots->shippingAddress($shippingAddress?->toArray()),
                    'price_cents' => $provisionalLineTotal,
                    'base_unit_price_cents' => (int) $offering->price_cents,
                    'configuration_adjustment_cents' => 0,
                    'final_unit_price_cents' => (int) $offering->price_cents,
                    'line_total_cents' => $provisionalLineTotal,
                    'currency' => strtoupper($offering->currency),
                ])->save();
            }

            $this->repriceOfferingGroup($user->id, $offering);
        });
    }

    public function updateAssetQuantity(CartItem $cartItem, int $quantity): void
    {
        $quantity = max(1, min(999, $quantity));
        $offering = $cartItem->assetOffering()->with([
            'asset.configurationGroups.values.rules',
            'pricingTiers',
            'licenseType',
        ])->firstOrFail();

        DB::transaction(function () use ($cartItem, $quantity, $offering): void {
            $cartItem->update(['quantity' => $quantity]);
            $this->repriceOfferingGroup($cartItem->user_id, $offering);
        });
    }

    public function remove(CartItem $cartItem): void
    {
        $userId = $cartItem->user_id;
        $offering = $cartItem->asset_offering_id
            ? $cartItem->assetOffering()->with([
                'asset.configurationGroups.values.rules',
                'pricingTiers',
                'licenseType',
            ])->first()
            : null;

        DB::transaction(function () use ($cartItem, $userId, $offering): void {
            $cartItem->delete();

            if ($offering) {
                $this->repriceOfferingGroup($userId, $offering);
            }
        });
    }

    public function repriceOfferingGroup(int $userId, AssetOffering $offering): void
    {
        $offering->loadMissing([
            'asset.configurationGroups.values.rules',
            'pricingTiers',
            'licenseType',
        ]);

        $items = CartItem::query()
            ->where('user_id', $userId)
            ->where('asset_id', $offering->asset_id)
            ->where('asset_offering_id', $offering->id)
            ->lockForUpdate()
            ->get();

        $aggregateQuantity = (int) $items->sum('quantity');

        foreach ($items as $item) {
            $selection = ConfigurationSelection::fromSnapshot(
                (array) $item->configuration_snapshot,
            );

            $quote = $this->pricingEngine->quote(
                $offering,
                $selection,
                (int) $item->quantity,
                $aggregateQuantity,
            );

            $item->update([
                'price_cents' => $quote->lineTotalCents,
                'base_unit_price_cents' => $quote->baseUnitPriceCents,
                'configuration_adjustment_cents' => $quote->configurationAdjustmentCents,
                'final_unit_price_cents' => $quote->finalUnitPriceCents,
                'line_total_cents' => $quote->lineTotalCents,
                'pricing_snapshot' => $this->snapshots->pricing($quote),
                'currency' => $quote->currency,
            ]);
        }
    }
}
