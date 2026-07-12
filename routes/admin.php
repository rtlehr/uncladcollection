<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminBlogPostController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDownloadController;
use App\Http\Controllers\Admin\AdminLicenseController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CommentModerationController;
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

        Route::post('/assets/{asset}/files', [AssetController::class, 'addFiles'])
            ->middleware('permission:manage_images')
            ->name('assets.files.store');

        Route::put('/assets/{asset}/files/order', [AssetController::class, 'reorderFiles'])
            ->middleware('permission:manage_images')
            ->name('assets.files.order');

        Route::patch('/assets/{asset}/files/{assetFile}', [AssetController::class, 'updateFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.update');

        Route::post('/assets/{asset}/files/{assetFile}/replace', [AssetController::class, 'replaceFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.replace');

        Route::delete('/assets/{asset}/files/{assetFile}', [AssetController::class, 'destroyFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.destroy');

        Route::resource('assets', AssetController::class)
            ->except(['show'])
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

        Route::get('/blog-posts/image-library', [AdminBlogPostController::class, 'imageLibrary'])
            ->middleware('permission:manage_blog_posts')
            ->name('blog-posts.image-library');

        Route::resource('blog-posts', AdminBlogPostController::class)
            ->middleware('permission:manage_blog_posts');

        Route::post('/blog-posts/upload-content-image', [AdminBlogPostController::class, 'uploadContentImage'])
            ->middleware('permission:manage_blog_posts')
            ->name('blog-posts.upload-content-image');
    });

Route::middleware(['auth', 'verified', 'permission:manage_comments'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/comments', [CommentModerationController::class, 'index'])
            ->name('comments.index');

        Route::get('/comments/reports', [CommentModerationController::class, 'reports'])
            ->name('comments.reports');

        Route::patch('/comments/{comment}/approve', [CommentModerationController::class, 'approve'])
            ->name('comments.approve');

        Route::patch('/comments/{comment}/hide', [CommentModerationController::class, 'hide'])
            ->name('comments.hide');

        Route::patch('/comments/{comment}/restore', [CommentModerationController::class, 'restore'])
            ->name('comments.restore');

        Route::patch('/comments/{comment}/pin', [CommentModerationController::class, 'pin'])
            ->name('comments.pin');

        Route::patch('/comments/{comment}/unpin', [CommentModerationController::class, 'unpin'])
            ->name('comments.unpin');

        Route::patch('/comments/{comment}/spam', [CommentModerationController::class, 'spam'])
            ->name('comments.spam');

        Route::delete('/comments/{comment}', [CommentModerationController::class, 'destroy'])
            ->name('comments.destroy');

        Route::patch('/comment-reports/{commentReport}/dismiss', [CommentModerationController::class, 'dismissReport'])
            ->name('comment-reports.dismiss');

        Route::patch('/comment-reports/{commentReport}/reviewed', [CommentModerationController::class, 'markReportReviewed'])
            ->name('comment-reports.reviewed');
    });