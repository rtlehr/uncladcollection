<?php

use App\Http\Controllers\Admin\SupportAttachmentController;
use App\Http\Controllers\Admin\SupportReportController;
use App\Http\Controllers\Admin\SupportTicketCategoryController;
use App\Http\Controllers\Admin\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'permission:view_admin'])->prefix('admin/support')->name('admin.support.')->group(function (): void {
    Route::get('/dashboard', [SupportTicketController::class, 'dashboard'])->middleware('permission:view_support_tickets')->name('dashboard');
    Route::get('/reports', SupportReportController::class)->middleware('permission:view_support_reports')->name('reports');
    Route::get('/tickets', [SupportTicketController::class, 'index'])->middleware('permission:view_support_tickets')->name('tickets.index');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->middleware('permission:view_support_tickets')->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->middleware('permission:reply_support_tickets')->name('tickets.reply');
    Route::post('/tickets/{ticket}/notes', [SupportTicketController::class, 'note'])->middleware('permission:add_support_internal_notes')->name('tickets.notes');
    Route::patch('/tickets/{ticket}', [SupportTicketController::class, 'update'])->middleware('permission:manage_support_tickets')->name('tickets.update');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [SupportAttachmentController::class, 'download'])->middleware('permission:view_support_tickets')->name('tickets.attachments.download');
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [SupportAttachmentController::class, 'redact'])->middleware('permission:manage_support_tickets')->name('tickets.attachments.redact');
    Route::resource('categories', SupportTicketCategoryController::class)->except(['create', 'show', 'edit'])->middleware('permission:manage_support_categories');
});
