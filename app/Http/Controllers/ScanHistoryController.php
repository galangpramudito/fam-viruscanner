<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScanResource;
use App\Models\ScanHistory;
use Illuminate\Http\JsonResponse;

class ScanHistoryController extends Controller
{
    public function show(ScanHistory $scan): JsonResponse
    {
        if ($scan->isExpired()) {
            return response()->json(['message' => 'Scan record has expired.'], 404);
        }

        return (new ScanResource($scan))->response();
    }
}
