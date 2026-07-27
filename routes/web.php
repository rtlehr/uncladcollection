<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MarketingCampaignTrackingController;
use App\Http\Controllers\PublicAdController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\PublicPageContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/marketing-campaigns/{marketingCampaign}/impression', [MarketingCampaignTrackingController::class, 'impression'])->name('marketing-campaigns.impression');
Route::post('/marketing-campaigns/{marketingCampaign}/click', [MarketingCampaignTrackingController::class, 'click'])->name('marketing-campaigns.click');
Route::get('/ads/placements/{placement}', [PublicAdController::class, 'show'])->name('ads.placements.show');
Route::post('/ads/creatives/{creative}/impression', [PublicAdController::class, 'impression'])->name('ads.creatives.impression');
Route::post('/ads/creatives/{creative}/click', [PublicAdController::class, 'click'])->name('ads.creatives.click');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')
        ->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/support.php';
require __DIR__.'/admin.php';
require __DIR__.'/favorite.php';
require __DIR__.'/user-images.php';
require __DIR__.'/checkout.php';
require __DIR__.'/user-blog.php';
require __DIR__.'/showcase.php';
require __DIR__.'/public-demo.php';
require __DIR__.'/seo.php';

require __DIR__.'/advertiser.php';
require __DIR__.'/admin-support.php';

Route::post('/{publicPage:slug}/contact', PublicPageContactController::class)->middleware('throttle:6,1')->name('public-pages.contact');

// Keep this catch-all public-page route last so application routes retain priority.
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public-pages.show');
