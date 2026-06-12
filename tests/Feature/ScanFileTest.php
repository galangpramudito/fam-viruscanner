<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Jobs\PerformScanJob;
use App\Models\ScanHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_scan_returns_202_with_status_url(): void
    {
        Queue::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('virus.apk', 100, 'application/vnd.android.package-archive');

        $response = $this->post('/api/scan-file', [
            'file' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(202);
        $response->assertJsonStructure(['scan_id', 'status', 'status_url']);

        $this->assertDatabaseHas('scan_histories', [
            'type' => 'file',
            'input_value' => 'virus.apk',
            'status' => ScanStatus::Pending->value,
        ]);
        Queue::assertPushed(PerformScanJob::class);
    }

    public function test_file_scan_idempotent_returns_existing_scan(): void
    {
        Queue::fake();
        Storage::fake('local');

        $content = 'identical-bytes-'.uniqid();

        $file = UploadedFile::fake()->createWithContent('dup.apk', $content, 'application/vnd.android.package-archive');

        $first = $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json']);
        $first->assertStatus(202);
        $firstScanId = $first->json('scan_id');

        $file2 = UploadedFile::fake()->createWithContent('dup-renamed.apk', $content, 'application/vnd.android.package-archive');
        $second = $this->post('/api/scan-file', ['file' => $file2], ['Accept' => 'application/json']);
        $second->assertStatus(202);
        $second->assertJson(['scan_id' => $firstScanId, 'source' => 'database_cache']);

        Queue::assertPushed(PerformScanJob::class, 1);
    }

    public function test_file_scan_rejects_oversized(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('big.apk', 21000, 'application/vnd.android.package-archive');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_scan_rejects_disallowed_mime(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('script.sh', 10, 'application/x-sh');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_scan_accepts_docx(): void
    {
        Queue::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->createWithContent(
            'report.docx',
            'PK'.str_repeat('x', 100)
        );

        $response = $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json']);
        $response->assertStatus(202);
    }

    public function test_dispatched_file_job_runs_to_completion(): void
    {
        Storage::fake('local');
        Http::fake([
            '*/files/*' => Http::response([
                'data' => [
                    'attributes' => [
                        'last_analysis_stats' => [
                            'malicious' => 2,
                            'suspicious' => 1,
                            'harmless' => 60,
                            'undetected' => 5,
                            'timeout' => 0,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $scan = ScanHistory::factory()
            ->file('sample.apk')
            ->state(['status' => ScanStatus::Pending])
            ->create([
                'result_json' => ['disk' => 'local', 'path' => 'scans/sample.apk'],
            ]);

        Storage::disk('local')->put('scans/sample.apk', 'fake-binary');

        (new PerformScanJob($scan->id))->handle(app(\App\Services\ScanService::class));

        $scan->refresh();
        $this->assertSame(ScanStatus::Completed, $scan->status);
        $this->assertSame(2, $scan->malicious_count);
        $this->assertSame(68, $scan->total_engines);
        $this->assertSame('suspicious', $scan->verdict?->value);
    }
}
