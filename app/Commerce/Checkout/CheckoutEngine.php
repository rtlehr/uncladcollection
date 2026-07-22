<?php

namespace App\Commerce\Checkout;

use App\Commerce\Cart\CartEngine;
use App\Models\CartItem;
use App\Models\License;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Enums\OrderFulfillmentStatus;

final class CheckoutEngine
{
    public const COMMERCE_VERSION = '2.0';

    public function __construct(
        private readonly CartEngine $cartEngine,
        private readonly CheckoutSnapshotFactory $snapshots,
        private readonly StripeMetadataBuilder $metadata,
    ) {}

    /** @param Collection<int, CartItem> $cartItems */
    public function prepareCartCheckout(User $user, Collection $cartItems): CheckoutPlan
    {
        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $cartItems->loadMissing([
            'image',
            'licenseType',
            'asset.configurationGroups.values.rules',
            'assetOffering.asset',
            'assetOffering.licenseType',
            'assetOffering.pricingTiers',
        ]);

        $this->validateCart($user, $cartItems);
        $this->repriceAssetGroups($user, $cartItems);

        $cartItems = CartItem::query()
            ->with([
                'image',
                'licenseType',
                'asset',
                'assetOffering.asset',
                'assetOffering.licenseType',
                'assetOffering.pricingTiers',
            ])
            ->where('user_id', $user->id)
            ->whereIn('id', $cartItems->pluck('id'))
            ->get();

        $currencies = $cartItems->pluck('currency')->map(fn ($value) => strtoupper((string) $value))->unique();
        if ($currencies->count() !== 1) {
            throw ValidationException::withMessages(['cart' => 'All cart items must use the same currency.']);
        }

        $order = DB::transaction(function () use ($user, $cartItems): Order {
            $subtotal = (int) $cartItems->sum(fn (CartItem $item) => (int) ($item->line_total_cents ?? $item->price_cents));

            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'fulfillment_status' => OrderFulfillmentStatus::New->value,
                'commerce_version' => self::COMMERCE_VERSION,
                'subtotal_cents' => $subtotal,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => $subtotal,
                'currency' => strtoupper((string) $cartItems->first()->currency),
                'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
                'checkout_snapshot' => $this->snapshots->order($cartItems),
                'checkout_locked_at' => now(),
                'metadata' => ['cart_item_ids' => $cartItems->pluck('id')->values()->all()],
            ]);

            foreach ($cartItems as $cartItem) {
                $this->createOrderItem($order, $cartItem);
            }

            return $order->load(['items.asset', 'items.assetOffering', 'items.image', 'items.licenseType']);
        });

        $lineItems = $order->items->map(fn (OrderItem $item): array => [
            'quantity' => (int) $item->quantity,
            'price_data' => [
                'currency' => strtolower($order->currency),
                'unit_amount' => (int) $item->unit_price_cents,
                'product_data' => [
                    'name' => $this->lineName($item),
                    'description' => $this->lineDescription($item),
                    'metadata' => array_filter([
                        'order_item_id' => (string) $item->id,
                        'asset_id' => $item->asset_id ? (string) $item->asset_id : null,
                        'asset_offering_id' => $item->asset_offering_id ? (string) $item->asset_offering_id : null,
                        'image_id' => $item->image_id ? (string) $item->image_id : null,
                        'configuration_hash' => $item->configuration_hash,
                    ]),
                ],
            ],
        ])->values()->all();

        return new CheckoutPlan(
            order: $order,
            lineItems: $lineItems,
            metadata: $this->metadata->forOrder($order, 'cart'),
            currency: $order->currency,
            totalCents: $order->total_cents,
        );
    }

    public function markPaid(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Order::STATUS_PAID) {
                return $locked->load(['items', 'licenses']);
            }

            if (! in_array($locked->status, [Order::STATUS_PENDING, Order::STATUS_FAILED], true)) {
                return $locked->load(['items', 'licenses']);
            }

            $locked->loadMissing(['items.licenseType', 'items.image', 'items.asset', 'items.assetOffering.licenseType']);
            $locked->update([
                'status' => Order::STATUS_PAID,
                'fulfillment_status' => OrderFulfillmentStatus::ReadyToPackage->value,
                'paid_at' => $locked->paid_at ?? now(),
            ]);

            foreach ($locked->items as $item) {
                $this->createLicense($locked, $item);
                $item->update(['status' => OrderItem::STATUS_ACTIVE]);

                if ($item->asset_id) {
                    $item->asset?->increment('purchases_count');
                } else {
                    $item->image?->increment('purchases_count');
                }
            }

            $cartItemIds = collect($locked->metadata['cart_item_ids'] ?? [])->filter()->map(fn ($id) => (int) $id);
            if ($cartItemIds->isNotEmpty()) {
                CartItem::query()->where('user_id', $locked->user_id)->whereIn('id', $cartItemIds)->delete();
            }

            return $locked->fresh(['items', 'licenses']);
        }, attempts: 3);
    }

    /** @param Collection<int, CartItem> $cartItems */
    private function validateCart(User $user, Collection $cartItems): void
    {
        foreach ($cartItems as $item) {
            if ($item->asset_id) {
                if (! $item->asset?->is_active || ! $item->assetOffering?->is_active) {
                    throw ValidationException::withMessages(['cart' => 'One or more assets or offerings are no longer available.']);
                }

                if ((int) $item->assetOffering->asset_id !== (int) $item->asset_id) {
                    throw ValidationException::withMessages(['cart' => 'A cart offering no longer belongs to its asset.']);
                }

                if ($item->asset->collects_shipping_address && $item->asset->shipping_address_required && empty($item->shipping_address_snapshot)) {
                    throw ValidationException::withMessages(['cart' => 'A mandatory shipping address is missing from one or more products.']);
                }
            } else {
                if (! $item->image?->is_active || ! $item->licenseType?->is_active) {
                    throw ValidationException::withMessages(['cart' => 'One or more legacy marketplace items are no longer available.']);
                }
            }

            if ((int) $item->quantity < 1 || (int) $item->quantity > 999) {
                throw ValidationException::withMessages(['cart' => 'One or more cart quantities are invalid.']);
            }
        }
    }

    /** @param Collection<int, CartItem> $cartItems */
    private function repriceAssetGroups(User $user, Collection $cartItems): void
    {
        $cartItems->whereNotNull('asset_offering_id')
            ->groupBy('asset_offering_id')
            ->each(function (Collection $items) use ($user): void {
                $offering = $items->first()->assetOffering;
                if ($offering) {
                    $this->cartEngine->repriceOfferingGroup($user->id, $offering);
                }
            });
    }

    private function createOrderItem(Order $order, CartItem $cartItem): OrderItem
    {
        $isAsset = $cartItem->asset_id !== null;
        $title = $isAsset ? $cartItem->asset?->title : $cartItem->image?->title;
        $licenseType = $cartItem->assetOffering?->licenseType ?? $cartItem->licenseType;
        $quantity = max(1, (int) $cartItem->quantity);
        $unit = (int) ($cartItem->final_unit_price_cents ?? $cartItem->price_cents);
        $total = (int) ($cartItem->line_total_cents ?? ($unit * $quantity));

        return OrderItem::create([
            'order_id' => $order->id,
            'image_id' => $cartItem->image_id,
            'asset_id' => $cartItem->asset_id,
            'license_type_id' => $cartItem->license_type_id,
            'asset_offering_id' => $cartItem->asset_offering_id,
            'status' => OrderItem::STATUS_PENDING,
            'fulfillment_type' => $cartItem->asset?->fulfillment_type?->value ?? 'digital',
            'commerce_version' => self::COMMERCE_VERSION,
            'quantity' => $quantity,
            'unit_price_cents' => $unit,
            'total_price_cents' => $total,
            'image_title' => (string) $title,
            'asset_title' => $isAsset ? (string) $title : null,
            'license_name' => (string) ($licenseType?->name ?? $cartItem->assetOffering?->name ?? 'License'),
            'offering_name' => $cartItem->assetOffering?->name,
            'license_terms' => $licenseType?->usage_terms,
            'configuration_hash' => $cartItem->configuration_hash,
            'configuration_snapshot' => $cartItem->configuration_snapshot,
            'shipping_address_snapshot' => $cartItem->shipping_address_snapshot,
            'pricing_snapshot' => $cartItem->pricing_snapshot,
            'included_asset_files_snapshot' => $this->snapshots->includedFiles($cartItem),
            'metadata' => [
                'cart_item_id' => $cartItem->id,
                'snapshot_version' => CheckoutSnapshotFactory::VERSION,
            ],
        ]);
    }

    private function createLicense(Order $order, OrderItem $item): License
    {
        $licenseType = $item->assetOffering?->licenseType ?? $item->licenseType;
        $startsAt = now();
        $expiresAfterDays = $item->assetOffering?->expires_after_days ?? $licenseType?->expires_after_days;

        return License::firstOrCreate(
            ['order_item_id' => $item->id],
            [
                'user_id' => $order->user_id,
                'image_id' => $item->image_id,
                'asset_id' => $item->asset_id,
                'order_id' => $order->id,
                'license_type_id' => $item->license_type_id,
                'asset_offering_id' => $item->asset_offering_id,
                'status' => License::STATUS_ACTIVE,
                'fulfillment_type' => $item->fulfillment_type,
                'commerce_version' => $item->commerce_version,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAfterDays ? $startsAt->copy()->addDays((int) $expiresAfterDays) : null,
                'download_limit' => $item->assetOffering?->download_limit ?? $licenseType?->download_limit,
                'downloads_used' => 0,
                'license_name' => $item->license_name,
                'license_terms' => $item->license_terms,
                'included_asset_files_snapshot' => $item->included_asset_files_snapshot,
                'configuration_snapshot' => $item->configuration_snapshot,
                'pricing_snapshot' => $item->pricing_snapshot,
                'metadata' => ['configuration_hash' => $item->configuration_hash],
            ],
        );
    }

    private function lineName(OrderItem $item): string
    {
        return trim(($item->asset_title ?: $item->image_title).' - '.($item->offering_name ?: $item->license_name));
    }

    private function lineDescription(OrderItem $item): ?string
    {
        $labels = collect($item->configuration_snapshot['labels'] ?? [])
            ->map(function (array $label): string {
                $values = collect($label['values'] ?? [])->filter()->implode(', ');

                return trim((string) ($label['group'] ?? '').': '.$values, ': ');
            })
            ->filter()
            ->implode('; ');

        return $labels !== '' ? $labels : null;
    }
}
