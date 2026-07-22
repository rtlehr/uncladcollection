<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketingCampaignTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/marketing-campaigns/{marketingCampaign}/impression', [MarketingCampaignTrackingController::class, 'impression'])->name('marketing-campaigns.impression');
Route::post('/marketing-campaigns/{marketingCampaign}/click', [MarketingCampaignTrackingController::class, 'click'])->name('marketing-campaigns.click');

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
