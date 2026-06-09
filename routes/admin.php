<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingController;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/site-settings', [SiteSettingController::class, 'index'])
            ->name('site-settings.index');

        Route::put('/site-settings', [SiteSettingController::class, 'update'])
            ->name('site-settings.update');

    });