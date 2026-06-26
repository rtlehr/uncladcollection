<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BlogCommentController;

Route::get('/blog', [BlogController::class, 'index'])
    ->name('blog.index');

Route::get('/blog/{blogPost}', [BlogController::class, 'show'])
    ->name('blog.show');

Route::get('/blog/{blogPost:slug}/comments', [BlogCommentController::class, 'index'])
    ->name('blog.comments.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/blog/{blogPost:slug}/comments', [BlogCommentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('blog.comments.store');

    Route::put('/comments/{comment}', [BlogCommentController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('comments.update');

    Route::delete('/comments/{comment}', [BlogCommentController::class, 'destroy'])
        ->name('comments.destroy');

    Route::post('/comments/{comment}/like', [BlogCommentController::class, 'toggleLike'])
        ->middleware('throttle:20,1')
        ->name('comments.like');

    Route::post('/comments/{comment}/report', [BlogCommentController::class, 'report'])
        ->middleware('throttle:5,1')
        ->name('comments.report');
});