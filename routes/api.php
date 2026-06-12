<?php

use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\ScanStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:scan')->group(function () {
    Route::post('/scan-url', [ScanController::class, 'scanUrl'])->name('api.scan.url');
    Route::post('/scan-file', [ScanController::class, 'scanFile'])->name('api.scan.file');
});

Route::get('/scans/{scan}/status', ScanStatusController::class)->name('api.scans.status');
Route::get('/scans/{scan}', [ScanHistoryController::class, 'show'])->name('api.scans.show');
