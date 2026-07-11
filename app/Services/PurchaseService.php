<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Image;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function createPendingOrder(
        User $user,
        Image $image,
        LicenseType $licenseType,
    ): Order {
        return DB::transaction(function () use ($user, $image, $licenseType) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'subtotal_cents' => $licenseType->price_cents,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => $licenseType->price_cents,
                'currency' => $licenseType->currency,
                'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'image_id' => $image->id,
                'license_type_id' => $licenseType->id,
                'status' => OrderItem::STATUS_PENDING,
                'quantity' => 1,
                'unit_price_cents' => $licenseType->price_cents,
                'total_price_cents' => $licenseType->price_cents,
                'image_title' => $image->title,
                'license_name' => $licenseType->name,
                'license_terms' => $licenseType->usage_terms,
            ]);

            return $order->load('items');
        });
    }

    public function createPendingOrderFromCart(
        User $user,
        Collection $cartItems,
    ): Order {
        return DB::transaction(function () use ($user, $cartItems) {
            $subtotalCents = $cartItems->sum('price_cents');
            $currency = $cartItems->first()->currency ?? 'USD';

            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => $subtotalCents,
                'currency' => $currency,
                'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            ]);

            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'image_id' => $cartItem->image_id,
                    'license_type_id' => $cartItem->license_type_id,
                    'status' => OrderItem::STATUS_PENDING,
                    'quantity' => 1,
                    'unit_price_cents' => $cartItem->price_cents,
                    'total_price_cents' => $cartItem->price_cents,
                    'image_title' => $cartItem->image->title,
                    'license_name' => $cartItem->licenseType->name,
                    'license_terms' => $cartItem->licenseType->usage_terms,
                ]);
            }

            return $order->load('items');
        });
    }

    public function markOrderPaid(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === Order::STATUS_PAID) {
                return $lockedOrder->load(['items', 'licenses']);
            }

            if (
                ! in_array(
                    $lockedOrder->status,
                    [Order::STATUS_PENDING, Order::STATUS_FAILED],
                    true,
                )
            ) {
                return $lockedOrder->load(['items', 'licenses']);
            }

            $lockedOrder->loadMissing('items.licenseType', 'items.image');

            $lockedOrder->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => $lockedOrder->paid_at ?? now(),
            ]);

            foreach ($lockedOrder->items as $item) {
                $license = $this->createLicenseFromOrderItem(
                    $lockedOrder,
                    $item,
                );

                $item->update([
                    'status' => OrderItem::STATUS_ACTIVE,
                ]);

                if ($license->wasRecentlyCreated) {
                    $item->image?->increment('purchases_count');
                }
            }

            CartItem::query()
                ->where('user_id', $lockedOrder->user_id)
                ->whereIn(
                    'image_id',
                    $lockedOrder->items->pluck('image_id'),
                )
                ->delete();

            return $lockedOrder->fresh(['items', 'licenses']);
        }, attempts: 3);
    }

    protected function createLicenseFromOrderItem(
        Order $order,
        OrderItem $item,
    ): License {
        $licenseType = $item->licenseType;
        $startsAt = now();

        $expiresAt = $licenseType->expires_after_days
            ? $startsAt->addDays($licenseType->expires_after_days)
            : null;

        return License::firstOrCreate(
            [
                'order_item_id' => $item->id,
            ],
            [
                'user_id' => $order->user_id,
                'image_id' => $item->image_id,
                'order_id' => $order->id,
                'license_type_id' => $item->license_type_id,
                'status' => License::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'download_limit' => $licenseType->download_limit,
                'downloads_used' => 0,
                'license_name' => $item->license_name,
                'license_terms' => $item->license_terms,
            ],
        );
    }

    public function userHasPurchasedImage(
        User $user,
        Image $image,
    ): bool {
        return License::query()
            ->where('user_id', $user->id)
            ->where('image_id', $image->id)
            ->where('status', License::STATUS_ACTIVE)
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function getActiveLicenseForImage(
        User $user,
        Image $image,
    ): ?License {
        return License::query()
            ->where('user_id', $user->id)
            ->where('image_id', $image->id)
            ->where('status', License::STATUS_ACTIVE)
            ->where(function ($query) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }
}
