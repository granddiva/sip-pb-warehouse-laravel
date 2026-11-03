<?php

use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DocumentAttachmentController;
use Illuminate\Support\Facades\Route;

// Printable physical slip
Route::get('/borrowings/{borrowing}/slip', [BorrowingController::class, 'slip'])
    ->name('borrowings.slip');

// React SPA Catch-all Route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
