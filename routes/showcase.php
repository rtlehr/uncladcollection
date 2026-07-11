<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Dev\ComponentShowcaseController;

Route::get('/dev/components', ComponentShowcaseController::class)
    ->middleware(['auth', 'can:view_admin'])
    ->name('dev.components');

Route::get('/dev/status-badges', function () {
    return Inertia::render('Dev/StatusBadgeShowcase');
})
    ->middleware(['auth', 'can:view_admin'])
    ->name('dev.status-badges');

Route::get('/dev/confirm-dialog', function () {
    return Inertia::render('Dev/ConfirmDialogShowcase');
})
    ->middleware(['auth', 'can:view_admin'])
    ->name('dev.confirm-dialog');

    Route::get('/dev/form-framework', function () {
    return Inertia::render('Dev/FormFrameworkShowcase');
})
    ->middleware(['auth', 'can:view_admin'])
    ->name('dev.form-framework');