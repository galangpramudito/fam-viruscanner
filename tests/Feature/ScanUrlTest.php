<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Jobs\PerformScanJob;
use App\Models\ScanHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ScanUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_scan_returns_202_with_status_url(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/scan-url', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure(['scan_id', 'status', 'status_url']);
        $this->assertSame('pending', $response->json('status'));
        $this->assertDatabaseHas('scan_histories', [
            'type' => 'url',
            'input_value' => 'https://example.com',
            'status' => ScanStatus::Pending->value,
        ]);
        Queue::assertPushed(PerformScanJob::class);
    }

    public function test_url_scan_idempotent_returns_existing_scan(): void
    {
        Queue::fake();

        $first = $this->postJson('/api/scan-url', ['url' => 'https://cached.example']);
        $first->assertStatus(202);
        $firstScanId = $first->json('scan_id');

        $second = $this->postJson('/api/scan-url', ['url' => 'https://cached.example']);
        $second->assertStatus(202);
        $second->assertJson(['scan_id' => $firstScanId, 'source' => 'database_cache']);

        Queue::assertPushed(PerformScanJob::class, 1);
    }

    public function test_url_scan_validation_rejects_bad_url(): void
    {
        $this->postJson('/api/scan-url', ['url' => 'not-a-url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_url_scan_validation_rejects_missing_url(): void
    {
        $this->postJson('/api/scan-url', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_url_scan_validation_rejects_oversized_url(): void
    {
        $longUrl = 'https://example.com/'.str_repeat('a', 2100);

        $this->postJson('/api/scan-url', ['url' => $longUrl])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_dispatched_job_runs_to_completion(): void
    {
        Http::fake([
            '*/urls/*' => Http::response([
                'data' => [
                    'attributes' => [
                        'last_analysis_stats' => [
                            'malicious' => 0,
                            'suspicious' => 0,
                            'harmless' => 70,
                            'undetected' => 5,
                            'timeout' => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $scan = ScanHistory::factory()->pending()->url('https://synced.example')->create();

        (new PerformScanJob($scan->id))->handle(app(\App\Services\ScanService::class));

        $scan->refresh();
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame(0, $scan->malicious_count);
        $this->assertSame(75, $scan->total_engines);
        $this->assertSame('safe', $scan->verdict?->value);
    }
}
