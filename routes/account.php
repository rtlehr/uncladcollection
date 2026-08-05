<?php

use App\Http\Controllers\Account\AccountDashboardController;
use App\Http\Controllers\Account\WishListController;
use App\Http\Controllers\Account\AccountOrderController;
use App\Http\Controllers\Account\PrivacyController;
use App\Http\Controllers\Account\LicenseDocumentController;
use App\Http\Controllers\Account\DesignProjectController;
use App\Http\Controllers\Account\DesignUploadController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\NotificationPreferenceController;
use App\Http\Controllers\Account\WishListItemController;
use App\Http\Controllers\AccountDownloadController;
use App\Http\Controllers\PurchaseBrowseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('account')
    ->name('account.')
    ->group(function (): void {
        Route::get('/', AccountDashboardController::class)->name('index');
        Route::get('/library', [PurchaseBrowseController::class, 'index'])->name('library.index');
        Route::get('/designs', [DesignProjectController::class, 'index'])->name('designs.index');
        Route::post('/licenses/{license}/designs', [DesignProjectController::class, 'store'])->middleware('block.impersonation')->name('designs.store');
        Route::get('/designs/{design}/edit', [DesignProjectController::class, 'edit'])->name('designs.edit');
        Route::put('/designs/{design}', [DesignProjectController::class, 'update'])->middleware('block.impersonation')->name('designs.update');
        Route::delete('/designs/{design}', [DesignProjectController::class, 'destroy'])->middleware('block.impersonation')->name('designs.destroy');
        Route::post('/designs/{design}/uploads', [DesignUploadController::class, 'store'])->middleware('block.impersonation')->name('designs.uploads.store');
        Route::get('/designs/{design}/uploads/{upload}', [DesignUploadController::class, 'show'])->name('designs.uploads.show');
        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::get('/privacy', [PrivacyController::class, 'edit'])->name('privacy.edit');
        Route::put('/privacy', [PrivacyController::class, 'update'])->middleware('block.impersonation')->name('privacy.update');
        Route::get('/licenses/{license}', [PurchaseBrowseController::class, 'showLicense'])->name('licenses.show');
        Route::get('/licenses/{license}/documents/certificate', [LicenseDocumentController::class, 'certificate'])->name('licenses.documents.certificate');
        Route::get('/licenses/{license}/documents/proof-of-purchase', [LicenseDocumentController::class, 'proofOfPurchase'])->name('licenses.documents.proof');
        Route::get('/licenses/{license}/files/{assetFile}/download', [AccountDownloadController::class, 'file'])
            ->middleware(['throttle:30,1', 'block.impersonation'])->name('licenses.files.download');
        Route::get('/licenses/{license}/download-all', [AccountDownloadController::class, 'package'])
            ->middleware(['throttle:10,1', 'block.impersonation'])->name('licenses.download-all');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'edit'])->name('notification-preferences.edit');
        Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');

        Route::get('/wish-lists', [WishListController::class, 'index'])->name('wish-lists.index');
        Route::post('/wish-lists', [WishListController::class, 'store'])->name('wish-lists.store');
        Route::get('/wish-lists/{wishList}', [WishListController::class, 'show'])->name('wish-lists.show');
        Route::patch('/wish-lists/{wishList}', [WishListController::class, 'update'])->name('wish-lists.update');
        Route::patch('/wish-lists/{wishList}/sharing', [WishListController::class, 'updateSharing'])->name('wish-lists.sharing');
        Route::patch('/wish-lists/{wishList}/notifications', [WishListController::class, 'updateNotifications'])->name('wish-lists.notifications');
        Route::delete('/wish-lists/{wishList}', [WishListController::class, 'destroy'])->name('wish-lists.destroy');
        Route::post('/wish-lists/{wishList}/assets/{asset}', [WishListItemController::class, 'store'])->name('wish-lists.items.store');
        Route::delete('/wish-lists/{wishList}/assets/{asset}', [WishListItemController::class, 'destroy'])->name('wish-lists.items.destroy');
        Route::patch('/wish-list-items/{wishListItem}/move', [WishListItemController::class, 'move'])->name('wish-list-items.move');

        Route::get('/favorites', [WishListController::class, 'legacyRedirect'])->name('favorites');
        Route::redirect('/profile', '/settings/profile')->name('profile');
        Route::redirect('/security', '/settings/security')->name('security');
        Route::redirect('/preferences/appearance', '/settings/appearance')->name('appearance');
    });
