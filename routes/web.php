<?php

use App\Models\BlogPost;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Dev\ComponentShowcaseController;

Route::get('/', function () {
    $latestArticles = BlogPost::query()
        ->published()
        ->with(['author:id,name', 'categories:id,name'])
        ->latest('published_at')
        ->take(3)
        ->get();

    return Inertia::render('Welcome', [
        'latestArticles' => $latestArticles,
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::get('/dev/components', ComponentShowcaseController::class)
    ->middleware(['auth', 'can:view_admin'])
    ->name('dev.components');
    
require __DIR__.'/settings.php';  
require __DIR__.'/admin.php'; 
require __DIR__.'/favorite.php'; 
require __DIR__.'/user-images.php'; 
require __DIR__.'/checkout.php'; 
require __DIR__.'/user-blog.php'; 
