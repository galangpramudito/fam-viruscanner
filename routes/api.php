<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::post('/scan-url', [ScanController::class, 'scanUrl']);
Route::post('/scan-file', [ScanController::class, 'scanFile']);