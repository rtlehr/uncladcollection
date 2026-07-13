<?php

namespace App\Services;

use App\Commerce\Checkout\CheckoutEngine;
use App\Models\CartItem;
use App\Models\Image;
use App\Models\LicenseType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Throwable;

class StripeCheckoutService
{
    public function __construct(
        protected PurchaseService $purchaseService,
        protected CheckoutEngine $checkoutEngine,
    ) {
        Stripe::setApiKey(config('stripe.secret'));
    }

    public function createCheckoutSession(User $user, Image $image, LicenseType $licenseType): Session
    {
        abort_unless($image->is_active, 404, 'This asset is not available for purchase.');
        abort_unless($licenseType->is_active, 404, 'This license is not available.');

        if ($this->purchaseService->userHasPurchasedImage($user, $image)) {
            throw ValidationException::withMessages(['image' => 'You already have an active license for this asset.']);
        }

        $order = $this->purchaseService->createPendingOrder($user, $image, $licenseType);

        try {
            $session = Session::create([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($licenseType->currency),
                        'unit_amount' => $licenseType->price_cents,
                        'product_data' => [
                            'name' => $image->title.' - '.$licenseType->name,
                            'description' => $licenseType->description,
                        ],
                    ],
                ]],
                'success_url' => url('/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/checkout/cancel'),
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => (string) $user->id,
                    'checkout_type' => 'single',
                    'commerce_version' => (string) ($order->commerce_version ?: '1.0'),
                ],
            ], ['idempotency_key' => 'checkout-order-'.$order->id]);
        } catch (Throwable $exception) {
            $order->update(['status' => Order::STATUS_FAILED]);
            throw $exception;
        }

        $order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }

    /** @param Collection<int, CartItem> $cartItems */
    public function createCartCheckoutSession(User $user, Collection $cartItems): Session
    {
        $plan = $this->checkoutEngine->prepareCartCheckout($user, $cartItems);

        try {
            $session = Session::create([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'line_items' => $plan->lineItems,
                'success_url' => url('/checkout/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/cart'),
                'metadata' => $plan->metadata,
            ], ['idempotency_key' => 'checkout-order-'.$plan->order->id]);
        } catch (Throwable $exception) {
            $plan->order->update(['status' => Order::STATUS_FAILED]);
            throw $exception;
        }

        $plan->order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }
}
