<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScanStatusResource;
use App\Models\ScanHistory;
use Illuminate\Http\JsonResponse;

class ScanStatusController extends Controller
{
    public function __invoke(ScanHistory $scan): JsonResponse
    {
        if ($scan->isExpired()) {
            return response()->json(['message' => 'Scan record has expired.'], 404);
        }

        return (new ScanStatusResource($scan))
            ->response();
    }
}
