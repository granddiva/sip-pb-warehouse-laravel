<?php

use App\Http\Controllers\BorrowingController;
use Illuminate\Support\Facades\Route;

// Printable physical slip
Route::get('/borrowings/{borrowing}/slip', [BorrowingController::class, 'slip'])
    ->name('borrowings.slip');

// React SPA Named Routes for internal linking & fallback
Route::get('/', function () {
    return view('app');
})->name('dashboard');

Route::get('/documents', function () {
    return view('app');
})->name('documents.index');

Route::get('/documents/{document}', function () {
    return view('app');
})->name('documents.show');

Route::get('/racks', function () {
    return view('app');
})->name('racks.index');

Route::get('/borrowings', function () {
    return view('app');
})->name('borrowings.index');

Route::get('/login', function () {
    return view('app');
})->name('login');

// React SPA Catch-all Route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
