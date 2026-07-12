<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('sitemap.index');

Route::get('/sitemaps/pages.xml', [SitemapController::class, 'pages'])
    ->name('sitemap.pages');

Route::get('/sitemaps/images.xml', [SitemapController::class, 'images'])
    ->name('sitemap.images');

Route::get('/sitemaps/articles.xml', [SitemapController::class, 'articles'])
    ->name('sitemap.articles');
