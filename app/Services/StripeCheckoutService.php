<?php

namespace App\Services;

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
    ) {
        Stripe::setApiKey(config('stripe.secret'));
    }

    public function createCheckoutSession(
        User $user,
        Image $image,
        LicenseType $licenseType,
    ): Session {
        abort_unless(
            $image->is_active,
            404,
            'This image is not available for purchase.',
        );

        abort_unless(
            $licenseType->is_active,
            404,
            'This license type is not available.',
        );

        if ($this->purchaseService->userHasPurchasedImage($user, $image)) {
            throw ValidationException::withMessages([
                'image' => 'You already have an active license for this image.',
            ]);
        }

        $order = $this->purchaseService->createPendingOrder(
            $user,
            $image,
            $licenseType,
        );

        try {
            $session = Session::create(
                [
                    'mode' => 'payment',
                    'customer_email' => $user->email,
                    'line_items' => [
                        [
                            'quantity' => 1,
                            'price_data' => [
                                'currency' => strtolower($licenseType->currency),
                                'unit_amount' => $licenseType->price_cents,
                                'product_data' => [
                                    'name' => $image->title.' - '.$licenseType->name,
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
                ],
                [
                    'idempotency_key' => 'checkout-order-'.$order->id,
                ],
            );
        } catch (Throwable $exception) {
            $order->update([
                'status' => Order::STATUS_FAILED,
            ]);

            throw $exception;
        }

        $order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }

    public function createCartCheckoutSession(
        User $user,
        Collection $cartItems,
    ): Session {
        $cartItems->loadMissing(['image', 'licenseType']);

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $currencies = $cartItems
            ->pluck('currency')
            ->filter()
            ->map(fn (string $currency) => strtoupper($currency))
            ->unique();

        if ($currencies->count() !== 1) {
            throw ValidationException::withMessages([
                'cart' => 'All cart items must use the same currency.',
            ]);
        }

        foreach ($cartItems as $cartItem) {
            if (! $cartItem->image?->is_active) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more images are no longer available.',
                ]);
            }

            if (! $cartItem->licenseType?->is_active) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more license types are no longer available.',
                ]);
            }

            if (
                $this->purchaseService->userHasPurchasedImage(
                    $user,
                    $cartItem->image,
                )
            ) {
                throw ValidationException::withMessages([
                    'cart' => "You already own an active license for {$cartItem->image->title}.",
                ]);
            }
        }

        $order = $this->purchaseService->createPendingOrderFromCart(
            $user,
            $cartItems,
        );

        $lineItems = $cartItems
            ->map(function (CartItem $cartItem): array {
                return [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($cartItem->currency),
                        'unit_amount' => $cartItem->price_cents,
                        'product_data' => [
                            'name' => $cartItem->image->title.' - '.$cartItem->licenseType->name,
                            'description' => $cartItem->licenseType->description,
                        ],
                    ],
                ];
            })
            ->values()
            ->all();

        try {
            $session = Session::create(
                [
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
                ],
                [
                    'idempotency_key' => 'checkout-order-'.$order->id,
                ],
            );
        } catch (Throwable $exception) {
            $order->update([
                'status' => Order::STATUS_FAILED,
            ]);

            throw $exception;
        }

        $order->update([
            'payment_provider' => Order::PAYMENT_PROVIDER_STRIPE,
            'stripe_checkout_session_id' => $session->id,
        ]);

        return $session;
    }
}
