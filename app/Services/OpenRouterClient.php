<?php

namespace App\Services;

use App\Enums\Verdict;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OpenRouterClient
{
    public const FALLBACK_EXPLANATION = 'Penjelasan AI tidak tersedia, namun sistem pemindai telah menyelesaikan pemeriksaan.';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'nex-agi/nex-n2-pro:free',
        private readonly string $baseUrl = 'https://openrouter.ai/api/v1',
        private readonly ?string $referer = null,
    ) {
    }

    public static function fromConfig(): self
    {
        if (config('services.tokenrouter.api_key')) {
            return new self(
                apiKey: (string) config('services.tokenrouter.api_key'),
                model: (string) config('services.tokenrouter.model', 'MiniMax-M3'),
                baseUrl: (string) config('services.tokenrouter.base_url', 'https://api.tokenrouter.com/v1'),
                referer: config('services.tokenrouter.referer') ?: config('app.url'),
            );
        }

        return new self(
            apiKey: (string) config('services.openrouter.api_key', ''),
            model: (string) config('services.openrouter.model', 'nex-agi/nex-n2-pro:free'),
            baseUrl: (string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'),
            referer: config('services.openrouter.referer') ?: config('app.url'),
        );
    }

    /**
     * Build the Indonesian-language explanation for a URL scan.
     */
    public function explainUrl(string $url, int $malicious, int $total): string
    {
        $verdict = Verdict::fromStats($malicious, $total);
        $technical = "Link: {$url}. Hasil scan: Terdeteksi BAHAYA oleh {$malicious} dari total {$total} mesin antivirus.";

        return $this->ask(
            $this->urlSystemPrompt($verdict),
            "Tolong buatkan penjelasan untuk data berikut: {$technical}"
        );
    }

    /**
     * Build the Indonesian-language explanation for a file scan.
     */
    public function explainFile(string $fileName, int $malicious, int $total): string
    {
        $verdict = Verdict::fromStats($malicious, $total);
        $technical = "Nama File: {$fileName}. Tipe: Aplikasi/Dokumen. Hasil scan HASH: Terdeteksi VIRUS/BAHAYA oleh {$malicious} dari total {$total} antivirus.";

        return $this->ask(
            $this->fileSystemPrompt($verdict),
            "Tolong buatkan penjelasan untuk file ini: {$technical}"
        );
    }

    private function ask(string $system, string $user): string
    {
        try {
            $response = $this->http()
                ->timeout(120)
                ->retry(3, 2000)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if (! $response->successful()) {
                \Illuminate\Support\Facades\Log::warning('AI provider returned non-2xx', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return self::FALLBACK_EXPLANATION;
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || $content === '') {
                \Illuminate\Support\Facades\Log::warning('AI provider returned empty content', [
                    'body' => $response->body(),
                ]);
                return self::FALLBACK_EXPLANATION;
            }

            // Remove <think>...</think> blocks from models that return reasoning
            $content = preg_replace('/<think>.*?<\/think>/s', '', $content);
            
            return trim($content);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AI provider request threw', [
                'error' => $e->getMessage(),
            ]);
            return self::FALLBACK_EXPLANATION;
        }
    }

    private function urlSystemPrompt(Verdict $verdict): string
    {
        $statusTag = match ($verdict) {
            Verdict::Safe => '[🟢 AMAN]',
            Verdict::Suspicious => '[🟡 WASPADA]',
            Verdict::Malicious => '[🔴 BAHAYA]',
        };

        return "Anda adalah asisten keamanan siber yang membantu masyarakat umum terhindar dari penipuan digital (phishing, scam, malware). Ubah laporan data keamanan menjadi penjelasan Bahasa Indonesia yang lugas, profesional, namun mudah dipahami orang awam. PENTING: Gunakan gaya bahasa universal yang netral. DILARANG KERAS menggunakan sapaan spesifik seperti \"Bapak\", \"Ibu\", \"Kakak\", atau \"Keluarga\". WAJIB gunakan format teks WhatsApp untuk penekanan (contoh: gunakan *teks* untuk huruf tebal). DILARANG menggunakan markdown standar seperti **. Di bagian atas, tuliskan status: {$statusTag} berdasarkan jumlah malicious. Berikan langkah konkret apa yang harus dilakukan.";
    }

    private function fileSystemPrompt(Verdict $verdict): string
    {
        $statusTag = $verdict === Verdict::Malicious
            ? '[🔴 BAHAYA / JANGAN DIINSTAL]'
            : ($verdict === Verdict::Suspicious ? '[🟡 WASPADA]' : '[🟢 AMAN]');

        return "Anda adalah asisten keamanan siber yang membantu masyarakat umum terhindar dari ancaman digital (APK palsu, dokumen berbahaya, virus). Ubah laporan keamanan file menjadi penjelasan Bahasa Indonesia yang lugas, profesional, namun mudah dipahami orang awam. PENTING: Gunakan gaya bahasa universal yang netral. DILARANG KERAS menggunakan sapaan spesifik seperti \"Bapak\", \"Ibu\", \"Kakak\", atau \"Keluarga\". WAJIB gunakan format teks WhatsApp untuk penekanan (contoh: gunakan *teks* untuk huruf tebal). DILARANG menggunakan markdown standar seperti **. Di bagian atas tulis status: {$statusTag}. Jelaskan risiko konkret jika file tersebut diinstal atau dibuka.";
    }

    private function http(): PendingRequest
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];

        if ($this->referer) {
            $headers['HTTP-Referer'] = $this->referer;
        }

        return Http::withHeaders($headers)->acceptJson();
    }
}
