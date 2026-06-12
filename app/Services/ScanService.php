<?php

namespace App\Services;

use App\Enums\ScanStatus;
use App\Enums\Verdict;
use App\Models\ScanHistory;
use App\ValueObjects\ScanResult;
use App\ValueObjects\VtAnalysisResult;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScanService
{
    public function __construct(
        private readonly VirusTotalClient $vt,
        private readonly OpenRouterClient $ai,
    ) {
    }

    public function runUrl(ScanHistory $scan): void
    {
        $scan->update(['status' => ScanStatus::Scanning]);

        try {
            $stats = $this->vt->lookupOrScanUrl($scan->input_value);
        } catch (Throwable $e) {
            $this->markFailed($scan, $e);
            return;
        }

        $this->persistResult($scan, $stats, fn () => $this->ai->explainUrl($scan->input_value, $stats->malicious, $stats->total));
    }

    public function runFile(ScanHistory $scan): void
    {
        $scan->update(['status' => ScanStatus::Scanning]);

        $disk = $scan->result_json['disk'] ?? 'local';
        $storedPath = $scan->result_json['path'] ?? null;

        if (! $storedPath) {
            $this->markFailed($scan, new \RuntimeException('Stored file path missing on scan row.'));
            return;
        }

        try {
            $absolutePath = \Storage::disk($disk)->path($storedPath);
            $stats = $this->vt->lookupOrScanFile($absolutePath, $scan->input_value);
        } catch (Throwable $e) {
            $this->markFailed($scan, $e);
            return;
        }

        $this->persistResult($scan, $stats, fn () => $this->ai->explainFile($scan->input_value, $stats->malicious, $stats->total));
    }

    /**
     * Persist verdict, counts, AI explanation, and raw stats JSON.
     */
    private function persistResult(ScanHistory $scan, VtAnalysisResult $stats, callable $aiCaller): void
    {
        $verdict = Verdict::fromStats($stats->malicious, $stats->total);
        $aiExplanation = $aiCaller();

        $scan->update([
            'malicious_count' => $stats->malicious,
            'total_engines' => $stats->total,
            'ai_explanation' => $aiExplanation,
            'verdict' => $verdict,
            'status' => ScanStatus::Completed,
            'result_json' => array_merge((array) $scan->result_json, [
                'stats' => [
                    'malicious' => $stats->malicious,
                    'suspicious' => $stats->suspicious,
                    'harmless' => $stats->harmless,
                    'undetected' => $stats->undetected,
                    'timeout' => $stats->timeout,
                ],
            ]),
        ]);
    }

    private function markFailed(ScanHistory $scan, Throwable $e): void
    {
        Log::warning('Scan failed', [
            'scan_id' => $scan->id,
            'type' => $scan->type,
            'error' => $e->getMessage(),
        ]);

        $scan->update([
            'status' => ScanStatus::Failed,
            'result_json' => array_merge((array) $scan->result_json, [
                'error' => $e->getMessage(),
            ]),
        ]);
    }

    public function buildResult(ScanHistory $scan): ScanResult
    {
        $stats = new VtAnalysisResult(
            malicious: (int) $scan->malicious_count,
            suspicious: (int) ($scan->result_json['stats']['suspicious'] ?? 0),
            harmless: (int) ($scan->result_json['stats']['harmless'] ?? 0),
            undetected: (int) ($scan->result_json['stats']['undetected'] ?? 0),
            timeout: (int) ($scan->result_json['stats']['timeout'] ?? 0),
            total: (int) $scan->total_engines,
        );

        return new ScanResult(
            stats: $stats,
            aiExplanation: (string) ($scan->ai_explanation ?? ''),
            verdict: $scan->verdict ?? Verdict::fromStats((int) $scan->malicious_count, (int) $scan->total_engines),
        );
    }
}
