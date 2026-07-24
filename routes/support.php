<?php

use App\Http\Controllers\Support\GuestSupportController;
use App\Http\Controllers\Support\MemberSupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('support')->name('support.')->group(function (): void {
    Route::get('/', [GuestSupportController::class, 'landing'])->name('landing');
    Route::get('/create', [GuestSupportController::class, 'create'])->name('create');
    Route::post('/', [GuestSupportController::class, 'store'])->middleware('throttle:support-public')->name('store');

    Route::get('/guest/{ticket}/{token}', [GuestSupportController::class, 'show'])->name('guest.show');
    Route::post('/guest/{ticket}/{token}/reply', [GuestSupportController::class, 'reply'])->middleware('throttle:support-guest-reply')->name('guest.reply');
    Route::get('/guest/{ticket}/{token}/attachments/{attachment}', [GuestSupportController::class, 'download'])->name('guest.attachments.download');

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('/tickets', [MemberSupportController::class, 'index'])->name('index');
        Route::get('/tickets/create', [MemberSupportController::class, 'create'])->name('member.create');
        Route::post('/tickets', [MemberSupportController::class, 'store'])->middleware('throttle:support-member-write')->name('member.store');
        Route::get('/tickets/{ticket}', [MemberSupportController::class, 'show'])->name('show');
        Route::post('/tickets/{ticket}/reply', [MemberSupportController::class, 'reply'])->middleware('throttle:support-member-write')->name('reply');
        Route::post('/tickets/{ticket}/close', [MemberSupportController::class, 'close'])->middleware('throttle:support-member-write')->name('close');
        Route::post('/tickets/{ticket}/reopen', [MemberSupportController::class, 'reopen'])->middleware('throttle:support-member-write')->name('reopen');
        Route::get('/tickets/{ticket}/attachments/{attachment}', [MemberSupportController::class, 'download'])->name('attachments.download');
    });
});
