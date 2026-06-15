<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CartController;

Route::middleware(['auth'])->group(function () {

    Route::post('/checkout/{image}', [CheckoutController::class, 'start'])
        ->name('checkout.start');

    Route::get('/checkout/success', [CheckoutController::class, 'success'])
        ->name('checkout.success');

    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])
        ->name('checkout.cancel');

    Route::get('/cart', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/cart/items', [CartController::class, 'store'])
        ->name('cart.items.store');

    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])
        ->name('cart.items.update');

    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])
        ->name('cart.items.destroy');

    Route::delete('/cart', [CartController::class, 'clear'])
        ->name('cart.clear');

    Route::post('/cart/checkout', [CartController::class, 'checkout'])
        ->name('cart.checkout');

});

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook');