<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\StripeWebhookController;

Route::middleware(['auth'])->group(function () {

    Route::post('/checkout/{image}', [CheckoutController::class, 'start'])
        ->name('checkout.start');

    Route::get('/checkout/success', [CheckoutController::class, 'success'])
        ->name('checkout.success');

    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])
        ->name('checkout.cancel');

});

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook');