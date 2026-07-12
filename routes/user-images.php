<?php

use App\Http\Controllers\CollectionBrowseController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ImageBrowseController;
use App\Http\Controllers\ImageFavoriteController;
use App\Http\Controllers\PurchaseBrowseController;
use Illuminate\Support\Facades\Route;

Route::get('/images', [ImageBrowseController::class, 'index'])
    ->name('images.index');

Route::get('/collections/{collection:slug}', [CollectionBrowseController::class, 'show'])
    ->name('collections.show');

Route::get('/images/{image:slug}', [ImageBrowseController::class, 'show'])
    ->name('images.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/images/{image}/download', [DownloadController::class, 'download'])
        ->middleware('throttle:30,1')
        ->name('images.download');

    Route::get('/purchases', [PurchaseBrowseController::class, 'index'])
        ->name('purchases.index');

    Route::get('/purchases/{image:slug}', [PurchaseBrowseController::class, 'show'])
        ->name('purchases.show');

    Route::get('/favorites', [ImageBrowseController::class, 'favorites'])
        ->name('images.favorites');

    Route::post('/images/{image}/favorite', [ImageFavoriteController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('images.favorite');

    Route::delete('/images/{image}/favorite', [ImageFavoriteController::class, 'destroy'])
        ->middleware('throttle:60,1')
        ->name('images.unfavorite');
});
