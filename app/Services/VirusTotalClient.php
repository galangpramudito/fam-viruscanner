<?php

namespace App\Services;

use App\Exceptions\VirusTotalTimeoutException;
use App\ValueObjects\VtAnalysisResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class VirusTotalClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://www.virustotal.com/api/v3',
        private readonly int $pollIntervalSeconds = 4,
        private readonly int $maxPollAttempts = 15,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            apiKey: (string) config('services.virustotal.api_key', ''),
            baseUrl: (string) config('services.virustotal.base_url', 'https://www.virustotal.com/api/v3'),
            pollIntervalSeconds: (int) config('services.virustotal.poll_interval_seconds', 4),
            maxPollAttempts: (int) config('services.virustotal.max_poll_attempts', 15),
        );
    }

    /**
     * Look up a URL by hash, or submit + poll on miss. Returns aggregated stats.
     */
    public function lookupOrScanUrl(string $url): VtAnalysisResult
    {
        $urlId = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');

        $lookup = $this->http()->get("{$this->baseUrl}/urls/{$urlId}");

        if ($lookup->status() === 404) {
            $submission = $this->http()->asForm()->post("{$this->baseUrl}/urls", [
                'url' => $url,
            ]);

            if (! $submission->successful()) {
                throw new \RuntimeException('Failed to submit URL to VirusTotal: HTTP '.$submission->status());
            }

            $analysisId = $submission->json('data.id');

            return $this->pollAnalysis($analysisId);
        }

        if (! $lookup->successful()) {
            throw new \RuntimeException('VirusTotal URL lookup failed: HTTP '.$lookup->status());
        }

        $stats = $lookup->json('data.attributes.last_analysis_stats');

        return VtAnalysisResult::fromStatsArray((array) $stats);
    }

    /**
     * Look up a file by SHA-256, or upload + poll on miss.
     *
     * @param  string  $path  Absolute path on local disk.
     * @param  string  $originalName  Filename to send in the multipart payload.
     */
    public function lookupOrScanFile(string $path, string $originalName): VtAnalysisResult
    {
        $hash = hash_file('sha256', $path);

        $lookup = $this->http()->get("{$this->baseUrl}/files/{$hash}");

        if ($lookup->status() === 404) {
            $upload = $this->http()
                ->attach('file', file_get_contents($path), $originalName)
                ->post("{$this->baseUrl}/files");

            if (! $upload->successful()) {
                throw new \RuntimeException('Failed to upload file to VirusTotal: HTTP '.$upload->status());
            }

            $analysisId = $upload->json('data.id');

            return $this->pollAnalysis($analysisId);
        }

        if (! $lookup->successful()) {
            throw new \RuntimeException('VirusTotal file lookup failed: HTTP '.$lookup->status());
        }

        $stats = $lookup->json('data.attributes.last_analysis_stats');

        return VtAnalysisResult::fromStatsArray((array) $stats);
    }

    private function pollAnalysis(string $analysisId): VtAnalysisResult
    {
        $attempts = 0;

        while ($attempts < $this->maxPollAttempts) {
            sleep($this->pollIntervalSeconds);
            $attempts++;

            $check = $this->http()->get("{$this->baseUrl}/analyses/{$analysisId}");

            if (! $check->successful()) {
                continue;
            }

            $status = $check->json('data.attributes.status');
            if ($status === 'completed') {
                $stats = $check->json('data.attributes.stats');

                return VtAnalysisResult::fromStatsArray((array) $stats);
            }
        }

        throw VirusTotalTimeoutException::exceeded($this->maxPollAttempts);
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
            'x-apikey' => $this->apiKey,
        ])->acceptJson();
    }
}
