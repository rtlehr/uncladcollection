<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/checkout/{image}', [CheckoutController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('checkout.start');

    Route::get('/checkout/success', [CheckoutController::class, 'success'])
        ->name('checkout.success');

    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])
        ->name('checkout.cancel');

    Route::get('/cart', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/cart/items', [CartController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('cart.items.store');

    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('cart.items.update');

    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])
        ->name('cart.items.destroy');

    Route::delete('/cart', [CartController::class, 'clear'])
        ->name('cart.clear');

    Route::post('/cart/checkout', [CartController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('cart.checkout');
});

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('stripe.webhook');
