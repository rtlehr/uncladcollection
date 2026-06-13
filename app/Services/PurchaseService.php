<?php

namespace App\Services;

use App\Models\Image;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Create a pending order with one image/license item.
     */
    public function createPendingOrder(
        User $user,
        Image $image,
        LicenseType $licenseType
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
                'payment_provider' => Order::PAYMENT_PROVIDER_MANUAL,
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

    /**
     * Mark an order as paid and create licenses for each order item.
     */
    public function markOrderPaid(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            
            $order->loadMissing('items.licenseType', 'items.image');

            $order->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);

            foreach ($order->items as $item) {
                $item->update([
                    'status' => OrderItem::STATUS_ACTIVE,
                ]);

                $this->createLicenseFromOrderItem($order, $item);

                $item->image?->increment('purchases_count');
            }

            return $order->fresh(['items', 'licenses']);
        });
    }

    /**
     * Create a license from an order item.
     */
    protected function createLicenseFromOrderItem(Order $order, OrderItem $item): License
    {
        $licenseType = $item->licenseType;

        $startsAt = now();

        $expiresAt = $licenseType->expires_after_days
            ? $startsAt->copy()->addDays($licenseType->expires_after_days)
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
            ]
        );
    }

    /**
     * Check if a user has an active license for an image.
     */
    public function userHasPurchasedImage(User $user, Image $image): bool
    {
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

    /**
     * Get the user's active license for an image.
     */
    public function getActiveLicenseForImage(User $user, Image $image): ?License
    {
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