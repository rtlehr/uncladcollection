<?php

use App\Http\Controllers\AssetBrowseController;
use App\Http\Controllers\AssetCardController;
use App\Http\Controllers\CollectionBrowseController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ImageBrowseController;
use App\Http\Controllers\ImageFavoriteController;
use App\Http\Controllers\PurchaseBrowseController;
use Illuminate\Support\Facades\Route;

Route::get('/images', [ImageBrowseController::class, 'index'])->name('images.index');
Route::get('/collections/{collection:slug}', [CollectionBrowseController::class, 'show'])->name('collections.show');
Route::get('/images/{image:slug}', [ImageBrowseController::class, 'show'])->name('images.show');

Route::get('/assets/{asset:slug}', [AssetBrowseController::class, 'show'])->name('assets.show');
Route::get('/assets/{asset}/preview/{assetFile}', [AssetBrowseController::class, 'preview'])
    ->middleware('throttle:120,1')
    ->name('assets.preview');
Route::get('/assets/{asset}/marketplace-preview', [AssetBrowseController::class, 'marketplacePreview'])
    ->middleware('throttle:120,1')
    ->name('assets.marketplace-preview');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/images/{image}/download', [DownloadController::class, 'download'])
        ->middleware('throttle:30,1')
        ->name('images.download');

    Route::get('/purchases', [PurchaseBrowseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/licenses/{license}', [PurchaseBrowseController::class, 'showLicense'])
        ->name('purchases.licenses.show');
    Route::get('/purchases/{image:slug}', [PurchaseBrowseController::class, 'show'])->name('purchases.show');
    Route::get('/favorites', [ImageBrowseController::class, 'favorites'])->name('images.favorites');
    Route::post('/images/{image}/favorite', [ImageFavoriteController::class, 'store'])
        ->middleware('throttle:60,1')->name('images.favorite');
    Route::delete('/images/{image}/favorite', [ImageFavoriteController::class, 'destroy'])
        ->middleware('throttle:60,1')->name('images.unfavorite');
});

Route::get('/assets/{asset:slug}/card-data', [AssetCardController::class, 'show'])
    ->name('assets.card-data');
