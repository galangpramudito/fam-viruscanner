<?php

namespace App\Jobs;

use App\Models\ScanHistory;
use App\Services\ScanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PerformScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 360;

    /** @var array<int, int> */
    public array $backoff = [10, 30];

    public function __construct(public int $scanHistoryId)
    {
    }

    public function handle(ScanService $service): void
    {
        $scan = ScanHistory::find($this->scanHistoryId);

        if (! $scan) {
            return;
        }

        if ($scan->type === 'url') {
            $service->runUrl($scan);
        } else {
            $service->runFile($scan);
        }
    }

    public function failed(?Throwable $e): void
    {
        $scan = ScanHistory::find($this->scanHistoryId);
        if (! $scan) {
            return;
        }

        Log::error('PerformScanJob failed terminally', [
            'scan_id' => $scan->id,
            'error' => $e?->getMessage(),
        ]);

        $scan->update([
            'status' => \App\Enums\ScanStatus::Failed,
            'result_json' => array_merge((array) $scan->result_json, [
                'error' => $e?->getMessage() ?? 'Unknown error',
            ]),
        ]);
    }
}
