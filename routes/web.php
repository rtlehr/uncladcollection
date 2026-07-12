<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')
        ->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/favorite.php';
require __DIR__.'/user-images.php';
require __DIR__.'/checkout.php';
require __DIR__.'/user-blog.php';
require __DIR__.'/showcase.php';
require __DIR__.'/public-demo.php';
require __DIR__.'/seo.php';
