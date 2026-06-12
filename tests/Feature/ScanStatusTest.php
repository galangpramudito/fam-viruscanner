<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Enums\Verdict;
use App\Models\ScanHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_returns_pending_payload(): void
    {
        $scan = ScanHistory::factory()->pending()->create();

        $response = $this->getJson("/api/scans/{$scan->id}/status");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scan->id,
                'status' => ScanStatus::Pending->value,
                'malicious_count' => 0,
                'total_engines' => 0,
                'progress' => 10,
            ],
        ]);
    }

    public function test_status_endpoint_returns_completed_payload_with_ai_explanation(): void
    {
        $scan = ScanHistory::factory()
            ->completed(malicious: 0, total: 70)
            ->create([
                'ai_explanation' => '🟢 AMAN: tidak ada ancaman terdeteksi.',
            ]);

        $response = $this->getJson("/api/scans/{$scan->id}/status");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scan->id,
                'status' => ScanStatus::Completed->value,
                'verdict' => Verdict::Safe->value,
                'malicious_count' => 0,
                'total_engines' => 70,
                'progress' => 100,
            ],
        ]);
        $response->assertJsonPath('data.ai_explanation', '🟢 AMAN: tidak ada ancaman terdeteksi.');
    }

    public function test_status_endpoint_returns_failed_payload_with_error(): void
    {
        $scan = ScanHistory::factory()->failed('VirusTotal API timeout')->create();

        $response = $this->getJson("/api/scans/{$scan->id}/status");

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scan->id,
                'status' => ScanStatus::Failed->value,
                'progress' => 100,
                'error' => 'VirusTotal API timeout',
            ],
        ]);
    }

    public function test_status_endpoint_404_for_expired_scan(): void
    {
        $scan = ScanHistory::factory()->expired()->create();

        $this->getJson("/api/scans/{$scan->id}/status")
            ->assertStatus(404)
            ->assertJson(['message' => 'Scan record has expired.']);
    }

    public function test_status_endpoint_404_for_missing_scan(): void
    {
        $this->getJson('/api/scans/99999/status')
            ->assertStatus(404);
    }

    public function test_history_endpoint_returns_full_resource(): void
    {
        $scan = ScanHistory::factory()
            ->completed(1, 70)
            ->create();

        $response = $this->getJson("/api/scans/{$scan->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'input_value',
                'status',
                'verdict',
                'malicious_count',
                'total_engines',
                'ai_explanation',
                'created_at',
                'expires_at',
            ],
        ]);
    }

    public function test_history_endpoint_404_for_expired_scan(): void
    {
        $scan = ScanHistory::factory()->expired()->create();

        $this->getJson("/api/scans/{$scan->id}")
            ->assertStatus(404);
    }
}
