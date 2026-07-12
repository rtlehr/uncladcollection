<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/public-page', function () {
    return Inertia::render('PublicPage');
})->name('public-page');
