<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDownloadController;
use App\Http\Controllers\Admin\AdminLicenseController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\LicenseTypeController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;

Route::middleware(['auth', 'verified', 'permission:view_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)
            ->name('dashboard');

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

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:manage_users')
            ->name('users.index');

        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('permission:manage_users')
            ->name('users.show');

        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:manage_users')
            ->name('users.edit');

        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:manage_users')
            ->name('users.update');

        Route::resource('categories', CategoryController::class)
            ->middleware('permission:manage_categories');

        Route::resource('tags', TagController::class)
            ->middleware('permission:manage_tags');

        Route::resource('collections', CollectionController::class)
            ->middleware('permission:manage_collections');

        Route::resource('images', ImageController::class)
            ->middleware('permission:manage_images');

        Route::resource('license-types', LicenseTypeController::class)
            ->except(['show'])
            ->middleware('permission:manage_license_types');

        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->middleware('permission:manage_orders')
            ->name('orders.index');

        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
            ->middleware('permission:manage_orders')
            ->name('orders.show');

        Route::get('/licenses', [AdminLicenseController::class, 'index'])
            ->middleware('permission:manage_licenses')
            ->name('licenses.index');

        Route::get('/licenses/{license}', [AdminLicenseController::class, 'show'])
            ->middleware('permission:manage_licenses')
            ->name('licenses.show');

        Route::get('/downloads', [AdminDownloadController::class, 'index'])
            ->middleware('permission:manage_downloads')
            ->name('downloads.index');

        Route::get('/downloads/{download}', [AdminDownloadController::class, 'show'])
            ->middleware('permission:manage_downloads')
            ->name('downloads.show');
    });