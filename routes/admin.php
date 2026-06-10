<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\ImageController;

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

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:manage_users')
            ->name('users.index');

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

    });