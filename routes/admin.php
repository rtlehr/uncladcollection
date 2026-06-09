<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;

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

        Route::resource('permissions', PermissionController::class)
            ->except(['show'])
            ->middleware('permission:manage_permissions');

        Route::resource('roles', RoleController::class)
            ->except(['show'])
            ->middleware('permission:manage_roles');

    });