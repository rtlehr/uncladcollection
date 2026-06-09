<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingController;

Route::middleware(['auth', 'verified', 'permission:view_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/site-settings', [SiteSettingController::class, 'index'])
            ->middleware('permission:manage_site_settings')
            ->name('site-settings.index');

        Route::put('/site-settings', [SiteSettingController::class, 'update'])
            ->middleware('permission:manage_site_settings')
            ->name('site-settings.update');

    });