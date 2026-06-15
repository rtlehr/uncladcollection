<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Image;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeCheckoutService
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
        Stripe::setApiKey(config('stripe.secret'));
    }

    public function createCheckoutSession(
        User $user,
        Image $image,
        LicenseType $licenseType
    ): Session {
        $order = $this->purchaseService->createPendingOrder(
            $user,
            $image,
            $licenseType
        );

        $session = Session::create([
            'mode' => 'payment',
            'customer_email' => $user->email,
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($licenseType->currency),
                        'unit_amount' => $licenseType->price_cents,
                        'product_data' => [
                            'name' => $image->title . ' - ' . $licenseType->name,
                            'description' => $licenseType->description,
                        ],
                    ],
                ],
            ],
            'success_url' => url('/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/checkout/cancel'),
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'user_id' => (string) $user->id,
                'checkout_type' => 'single',
                'image_id' => (string) $image->id,
                'license_type_id' => (string) $licenseType->id,
            ],
        ]);

        $order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }

    /**
     * Create a Stripe Checkout session from the user's cart.
     */
    public function createCartCheckoutSession(User $user, Collection $cartItems): Session
    {
        $cartItems->loadMissing(['image', 'licenseType']);

        $order = $this->purchaseService->createPendingOrderFromCart(
            $user,
            $cartItems
        );

        $lineItems = $cartItems->map(function (CartItem $cartItem) {
            return [
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($cartItem->currency),
                    'unit_amount' => $cartItem->price_cents,
                    'product_data' => [
                        'name' => $cartItem->image->title . ' - ' . $cartItem->licenseType->name,
                        'description' => $cartItem->licenseType->description,
                    ],
                ],
            ];
        })->values()->all();

        $session = Session::create([
            'mode' => 'payment',
            'customer_email' => $user->email,
            'line_items' => $lineItems,
            'success_url' => url('/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/cart'),
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
                'user_id' => (string) $user->id,
                'checkout_type' => 'cart',
            ],
        ]);

        $order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }
}