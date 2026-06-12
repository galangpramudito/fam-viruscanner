<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatus;
use App\Http\Requests\ScanFileRequest;
use App\Http\Requests\ScanUrlRequest;
use App\Jobs\PerformScanJob;
use App\Models\ScanHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ScanController extends Controller
{
    public function scanUrl(ScanUrlRequest $request): JsonResponse
    {
        $url = $request->string('url')->toString();

        $existing = ScanHistory::query()->forUrl($url)->latest('id')->first();
        if ($existing) {
            return response()->json([
                'scan_id' => $existing->id,
                'status' => $existing->status?->value,
                'status_url' => route('api.scans.status', $existing->id),
                'source' => 'database_cache',
            ], 202);
        }

        $scan = ScanHistory::create([
            'type' => 'url',
            'input_value' => $url,
            'status' => ScanStatus::Pending,
        ]);

        PerformScanJob::dispatch($scan->id);

        return response()->json([
            'scan_id' => $scan->id,
            'status' => $scan->status->value,
            'status_url' => route('api.scans.status', $scan->id),
        ], 202);
    }

    public function scanFile(ScanFileRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $hash = hash_file('sha256', $file->getRealPath());

        $existing = ScanHistory::query()->forFileHash($hash)->latest('id')->first();
        if ($existing) {
            return response()->json([
                'scan_id' => $existing->id,
                'status' => $existing->status?->value,
                'status_url' => route('api.scans.status', $existing->id),
                'source' => 'database_cache',
            ], 202);
        }

        $path = $file->store('scans');

        $scan = ScanHistory::create([
            'type' => 'file',
            'input_value' => $originalName,
            'file_hash' => $hash,
            'status' => ScanStatus::Pending,
            'result_json' => [
                'disk' => config('filesystems.default', 'local'),
                'path' => $path,
            ],
        ]);

        PerformScanJob::dispatch($scan->id);

        return response()->json([
            'scan_id' => $scan->id,
            'status' => $scan->status->value,
            'status_url' => route('api.scans.status', $scan->id),
        ], 202);
    }
}
