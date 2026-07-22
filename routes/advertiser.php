<?php

use App\Http\Controllers\AdvertiserPortal\AccountController;
use App\Http\Controllers\AdvertiserPortal\CampaignController;
use App\Http\Controllers\AdvertiserPortal\CreativeController;
use App\Http\Controllers\AdvertiserPortal\DashboardController;
use App\Http\Controllers\AdvertiserPortal\InvoiceController;
use App\Http\Controllers\AdvertiserPortal\ProposalController;
use App\Http\Controllers\AdvertiserPortal\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'advertiser.portal'])
    ->prefix('advertiser')->name('advertiser.')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::get('/campaigns/{campaign}/creatives', [CreativeController::class, 'index'])->name('campaigns.creatives.index');
        Route::get('/campaigns/{campaign}/creatives/create', [CreativeController::class, 'create'])->name('campaigns.creatives.create');
        Route::post('/campaigns/{campaign}/creatives', [CreativeController::class, 'store'])->name('campaigns.creatives.store');
        Route::get('/campaigns/{campaign}/creatives/{creative}/edit', [CreativeController::class, 'edit'])->name('campaigns.creatives.edit');
        Route::put('/campaigns/{campaign}/creatives/{creative}', [CreativeController::class, 'update'])->name('campaigns.creatives.update');
        Route::post('/campaigns/{campaign}/creatives/{creative}/submit', [CreativeController::class, 'submit'])->name('campaigns.creatives.submit');
        Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
        Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
        Route::post('/proposals/{proposal}/accept', [ProposalController::class, 'accept'])->name('proposals.accept');
        Route::post('/proposals/{proposal}/decline', [ProposalController::class, 'decline'])->name('proposals.decline');
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/checkout', [InvoiceController::class, 'checkout'])->name('invoices.checkout');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    });
