<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageBrowseController;
use App\Http\Controllers\ImageFavoriteController;

Route::get('/images', [ImageBrowseController::class, 'index'])
    ->name('images.index');

Route::get('/images/{image:slug}', [ImageBrowseController::class, 'show'])
    ->name('images.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/favorites', [ImageBrowseController::class, 'favorites'])
        ->name('images.favorites');

    Route::post('/images/{image}/favorite', [ImageFavoriteController::class, 'store'])
        ->name('images.favorite');

    Route::delete('/images/{image}/favorite', [ImageFavoriteController::class, 'destroy'])
        ->name('images.unfavorite');
});

