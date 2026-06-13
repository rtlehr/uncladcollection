<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageBrowseController;
use App\Http\Controllers\ImageFavoriteController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\PurchaseBrowseController;

Route::get('/images', [ImageBrowseController::class, 'index'])
    ->name('images.index');

Route::get('/images/{image:slug}', [ImageBrowseController::class, 'show'])
    ->name('images.show');

Route::middleware('auth')->group(function () {
    Route::post(
        '/images/{image}/purchase',
        [PurchaseController::class, 'purchase']
    )->name('images.purchase');
});

Route::middleware('auth')->group(function () {
    Route::get('/images/{image}/download', [DownloadController::class, 'download'])
        ->name('images.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/purchases', [PurchaseBrowseController::class, 'index'])
        ->name('purchases.index');

    Route::get('/purchases/{image:slug}', [PurchaseBrowseController::class, 'show'])
        ->name('purchases.show');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/favorites', [ImageBrowseController::class, 'favorites'])
        ->name('images.favorites');

    Route::post('/images/{image}/favorite', [ImageFavoriteController::class, 'store'])
        ->name('images.favorite');

    Route::delete('/images/{image}/favorite', [ImageFavoriteController::class, 'destroy'])
        ->name('images.unfavorite');
});


