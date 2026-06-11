<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageFavoriteController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/images/{image}/favorite', [ImageFavoriteController::class, 'store'])
        ->name('images.favorite');

    Route::delete('/images/{image}/favorite', [ImageFavoriteController::class, 'destroy'])
        ->name('images.unfavorite');
});