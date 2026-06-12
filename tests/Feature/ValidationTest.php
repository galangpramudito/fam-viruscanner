<?php

namespace Tests\Feature;

use App\Enums\ScanStatus;
use App\Models\ScanHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_required(): void
    {
        $this->postJson('/api/scan-url', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_url_must_be_http_or_https(): void
    {
        $this->postJson('/api/scan-url', ['url' => 'ftp://example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_url_max_length_enforced(): void
    {
        $url = 'https://x.com/'.str_repeat('a', 2050);

        $this->postJson('/api/scan-url', ['url' => $url])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_url_accepts_valid_https(): void
    {
        Queue::fake();
        $this->postJson('/api/scan-url', ['url' => 'https://example.com'])
            ->assertStatus(202);
    }

    public function test_file_required(): void
    {
        $this->post('/api/scan-file', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_max_size_enforced(): void
    {
        $file = UploadedFile::fake()->create('big.apk', 25000, 'application/vnd.android.package-archive');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_disallowed_mime_rejected(): void
    {
        $file = UploadedFile::fake()->create('image.png', 100, 'image/png');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_file_apk_accepted(): void
    {
        Queue::fake();
        $file = UploadedFile::fake()->createWithContent('clean.apk', 'apk-content', 'application/vnd.android.package-archive');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(202);
    }

    public function test_file_pdf_accepted(): void
    {
        Queue::fake();
        $file = UploadedFile::fake()->createWithContent('doc.pdf', '%PDF-1.4 fake', 'application/pdf');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(202);
    }

    public function test_file_zip_accepted(): void
    {
        Queue::fake();
        $file = UploadedFile::fake()->createWithContent('bundle.zip', "PK\x03\x04fake", 'application/zip');

        $this->post('/api/scan-file', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(202);
    }
}
