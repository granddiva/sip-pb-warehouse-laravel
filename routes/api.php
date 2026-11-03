<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BorrowingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\RackController;
use App\Http\Controllers\Api\ScannerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    // Auth
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Protected API Routes
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Documents
        Route::get('/documents/export', [DocumentController::class, 'export']);
        Route::get('/documents/generate-number', [DocumentController::class, 'generateNumber']);
        Route::post('/documents/relocate', [DocumentController::class, 'relocate']);
        Route::get('/documents', [DocumentController::class, 'index']);
        Route::get('/documents/{document}', [DocumentController::class, 'show']);
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::post('/documents/{document}/verify-physical', [DocumentController::class, 'verifyPhysical']);
        Route::put('/documents/{document}', [DocumentController::class, 'update']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

        // Attachments
        Route::post('/documents/{document}/attachments', [AttachmentController::class, 'store']);
        Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

        // Racks & Boxes (2D Warehouse Grid)
        Route::get('/racks', [RackController::class, 'index']);
        Route::get('/boxes/{box}/documents', [RackController::class, 'boxDocuments']);
        Route::post('/boxes/{box}/toggle-lock', [RackController::class, 'toggleLock']);

        // Sirkulasi Peminjaman
        Route::get('/borrowings/export', [BorrowingController::class, 'export']);
        Route::get('/borrowings', [BorrowingController::class, 'index']);
        Route::get('/borrowings/available-docs', [BorrowingController::class, 'availableDocs']);
        Route::post('/borrowings', [BorrowingController::class, 'store']);
        Route::post('/borrowings/{borrowing}/return', [BorrowingController::class, 'returnDoc']);

        // QR Codes & Scanner
        Route::get('/qrcodes/document/{document}', [QrCodeController::class, 'document']);
        Route::get('/qrcodes/box/{box}', [QrCodeController::class, 'box']);
        Route::get('/qrcodes/batch', [QrCodeController::class, 'batch']);
        Route::get('/scan/resolve', [ScannerController::class, 'resolve']);

        // Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    });
});
