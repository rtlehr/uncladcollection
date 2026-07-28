<?php

use App\Http\Controllers\Account\AccountDashboardController;
use App\Http\Controllers\PurchaseBrowseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('account')
    ->name('account.')
    ->group(function (): void {
        Route::get('/', AccountDashboardController::class)->name('index');
        Route::get('/library', [PurchaseBrowseController::class, 'index'])->name('library.index');
        Route::get('/licenses/{license}', [PurchaseBrowseController::class, 'showLicense'])->name('licenses.show');
        Route::get('/favorites', fn () => redirect()->route('images.favorites'))->name('favorites');
        Route::redirect('/profile', '/settings/profile')->name('profile');
        Route::redirect('/security', '/settings/security')->name('security');
        Route::redirect('/preferences/appearance', '/settings/appearance')->name('appearance');
    });
