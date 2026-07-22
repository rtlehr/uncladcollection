<?php

namespace App\Services;

use App\Commerce\Checkout\CheckoutEngine;
use App\Enums\OrderFulfillmentStatus;
use App\Models\Image;
use App\Models\License;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private readonly CheckoutEngine $checkoutEngine) {}

    public function createPendingOrder(User $user, Image $image, LicenseType $licenseType): Order
    {
        return DB::transaction(function () use ($user, $image, $licenseType): Order {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => Order::STATUS_PENDING,
                'fulfillment_status' => OrderFulfillmentStatus::New->value,
                'commerce_version' => '1.0',
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
                'fulfillment_type' => 'digital',
                'commerce_version' => '1.0',
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

    public function markOrderPaid(Order $order): Order
    {
        return $this->checkoutEngine->markPaid($order);
    }

    public function userHasPurchasedImage(User $user, Image $image): bool
    {
        return License::query()
            ->where('user_id', $user->id)
            ->where('image_id', $image->id)
            ->where('status', License::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    public function getActiveLicenseForImage(User $user, Image $image): ?License
    {
        return License::query()
            ->where('user_id', $user->id)
            ->where('image_id', $image->id)
            ->where('status', License::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->first();
    }
}
