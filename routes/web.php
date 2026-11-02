<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DocumentAttachmentController;
use Illuminate\Support\Facades\Route;

// File download & inline streaming (native browser rendering)
Route::get('/attachments/{attachment}/download', [DocumentAttachmentController::class, 'download'])
    ->name('attachments.download');
Route::get('/attachments/{attachment}/preview', [DocumentAttachmentController::class, 'preview'])
    ->name('attachments.preview');

// Printable physical slip
Route::get('/borrowings/{borrowing}/slip', [BorrowingController::class, 'slip'])
    ->name('borrowings.slip');

// React SPA Catch-all Route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
