<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScanHistory;
use Illuminate\Support\Facades\Http;

class ScanController extends Controller
{
    // Fungsi Utama untuk Scan URL
    public function scanUrl(Request $request)
    {
        // Beri waktu PHP mengeksekusi script hingga 3 menit agar tidak RTO saat menunggu VirusTotal & AI
        set_time_limit(180);

        $request->validate([
            'url' => 'required|url'
        ]);

        $targetUrl = $request->input('url');

        // 1. Cek Database Cache
        $existingScan = ScanHistory::where('type', 'url')
                                    ->where('input_value', $targetUrl)
                                    ->first();

        if ($existingScan) {
            return response()->json([
                'status' => 'success',
                'source' => 'database_cache',
                'data' => $existingScan
            ]);
        }

        // 2. Cek ke VirusTotal
        $urlId = rtrim(strtr(base64_encode($targetUrl), '+/', '-_'), '=');
        $vtResponse = Http::withHeaders([
            'x-apikey' => env('VIRUSTOTAL_API_KEY')
        ])->get("https://www.virustotal.com/api/v3/urls/{$urlId}");

        $stats = null;

        // 3. JIKA LINK BARU (404) -> POST DAN TUNGGU OTOMATIS (POLLING)
        if ($vtResponse->status() == 404) {
            $postResponse = Http::withHeaders([
                'x-apikey' => env('VIRUSTOTAL_API_KEY')
            ])->asForm()->post('https://www.virustotal.com/api/v3/urls', [
                'url' => $targetUrl
            ]);

            if (!$postResponse->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mendaftarkan pemeriksaan link ke server keamanan.'
                ], 400);
            }

            // Ambil ID Analisis dari response POST
            $analysisId = $postResponse->json()['data']['id'];
            
            // SISTEM POLLING: Cek status analisis setiap 4 detik, maksimal 15 kali percobaan (1 menit)
            $status = 'queued';
            $attempts = 0;
            
            while ($status !== 'completed' && $attempts < 15) {
                sleep(4); // Jeda 4 detik sebelum nanya lagi ke VirusTotal
                
                $analysisCheck = Http::withHeaders([
                    'x-apikey' => env('VIRUSTOTAL_API_KEY')
                ])->get("https://www.virustotal.com/api/v3/analyses/{$analysisId}");
                
                if ($analysisCheck->successful()) {
                    $status = $analysisCheck->json()['data']['attributes']['status'];
                    if ($status === 'completed') {
                        $stats = $analysisCheck->json()['data']['attributes']['stats'];
                        break;
                    }
                }
                $attempts++;
            }

            // Jika setelah 1 menit masih belum selesai juga
            if ($status !== 'completed' || !$stats) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sistem pemindai global sedang sangat padat. Mohon coba klik tombol scan lagi nanti.'
                ], 408);
            }
        } else if ($vtResponse->status() == 429) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sistem sedang sibuk. Tunggu 1 menit lalu coba lagi. 🙏'
            ], 429);
        } else if (!$vtResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memeriksa link ke server keamanan.'
            ], $vtResponse->status());
        } else {
            // Jika status 200 OK dari awal (Sudah ada di VT)
            $stats = $vtResponse->json()['data']['attributes']['last_analysis_stats'];
        }

        // 4. Hitung Statistik & Kirim ke AI
        $maliciousCount = $stats['malicious'];
        $harmlessCount = $stats['harmless'];
        $undeterminedCount = ($stats['suspicious'] ?? 0) + ($stats['timeout'] ?? 0);
        $totalEngines = $maliciousCount + $harmlessCount + $undeterminedCount;

        $technicalReport = "Link: {$targetUrl}. Hasil scan: Terdeteksi BAHAYA oleh {$maliciousCount} dari total {$totalEngines} mesin antivirus.";

        $aiResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'HTTP-Referer' => env('APP_URL', 'http://localhost'),
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->retry(3, 2000)
        ->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'nex-agi/nex-n2-pro:free', 
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten keamanan siber yang membantu masyarakat umum terhindar dari penipuan digital (phishing, scam, malware). Ubah laporan data keamanan menjadi penjelasan Bahasa Indonesia yang lugas, profesional, namun mudah dipahami orang awam. PENTING: Gunakan gaya bahasa universal yang netral. DILARANG KERAS menggunakan sapaan spesifik seperti "Bapak", "Ibu", "Kakak", atau "Keluarga". WAJIB gunakan format teks WhatsApp untuk penekanan (contoh: gunakan *teks* untuk huruf tebal). DILARANG menggunakan markdown standar seperti **. Di bagian atas, tuliskan status: [🔴 BAHAYA], [🟡 WASPADA], atau [🟢 AMAN] berdasarkan jumlah malicious. Berikan langkah konkret apa yang harus dilakukan.'
                ],
                [
                    'role' => 'user',
                    'content' => "Tolong buatkan penjelasan untuk data berikut: {$technicalReport}"
                ]
            ]
        ]);

        $aiExplanation = "Penjelasan AI tidak tersedia, namun sistem mendeteksi indikasi pada link ini.";
        if ($aiResponse->successful()) {
            $aiExplanation = $aiResponse->json()['choices'][0]['message']['content'];
        }

        // 5. Simpan & Kembalikan Respons
        $newScan = ScanHistory::create([
            'type' => 'url',
            'input_value' => $targetUrl,
            'file_hash' => null,
            'malicious_count' => $maliciousCount,
            'total_engines' => $totalEngines,
            'ai_explanation' => $aiExplanation,
        ]);

        return response()->json([
            'status' => 'success',
            'source' => 'live_api',
            'data' => $newScan
        ]);
    }

    // Fungsi Utama untuk Scan File
    public function scanFile(Request $request)
    {
        set_time_limit(180); // Waktu eksekusi PHP maksimal 3 menit

        $request->validate([
            'file' => 'required|file|max:20480|mimetypes:application/vnd.android.package-archive,application/x-msdownload,application/pdf,application/msword'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileHash = hash_file('sha256', $file->getRealPath());

        // 1. Cek Database Cache
        $existingScan = ScanHistory::where('type', 'file')
                                    ->where('file_hash', $fileHash)
                                    ->first();

        if ($existingScan) {
            return response()->json([
                'status' => 'success',
                'source' => 'database_cache',
                'data' => $existingScan
            ]);
        }

        // 2. Cek ke VirusTotal via HASH
        $vtResponse = Http::withHeaders([
            'x-apikey' => env('VIRUSTOTAL_API_KEY')
        ])->get("https://www.virustotal.com/api/v3/files/{$fileHash}");

        $stats = null;

        // 3. JIKA FILE BARU (404) -> UPLOAD DAN TUNGGU OTOMATIS (POLLING)
        if ($vtResponse->status() == 404) {
            $uploadResponse = Http::withHeaders([
                'x-apikey' => env('VIRUSTOTAL_API_KEY')
            ])->attach(
                'file', file_get_contents($file->getRealPath()), $originalName
            )->post('https://www.virustotal.com/api/v3/files');

            if (!$uploadResponse->successful()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengunggah file ke server pemindai keamanan.'
                ], 400);
            }

            // Ambil ID Analisis dari response Upload
            $analysisId = $uploadResponse->json()['data']['id'];
            
            // SISTEM POLLING
            $status = 'queued';
            $attempts = 0;
            
            while ($status !== 'completed' && $attempts < 15) {
                sleep(4); // Jeda 4 detik
                
                $analysisCheck = Http::withHeaders([
                    'x-apikey' => env('VIRUSTOTAL_API_KEY')
                ])->get("https://www.virustotal.com/api/v3/analyses/{$analysisId}");
                
                if ($analysisCheck->successful()) {
                    $status = $analysisCheck->json()['data']['attributes']['status'];
                    if ($status === 'completed') {
                        $stats = $analysisCheck->json()['data']['attributes']['stats'];
                        break;
                    }
                }
                $attempts++;
            }

            if ($status !== 'completed' || !$stats) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sistem pemindai global sedang memproses file terlalu lama. Silakan coba lagi nanti.'
                ], 408);
            }
        } else if ($vtResponse->status() == 429) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sistem sedang sibuk. Tunggu 1 menit lalu coba lagi. 🙏'
            ], 429);
        } else if (!$vtResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memeriksa file ke server keamanan.'
            ], $vtResponse->status());
        } else {
            // Jika status 200 OK dari awal
            $stats = $vtResponse->json()['data']['attributes']['last_analysis_stats'];
        }

        // 4. Hitung Statistik & Kirim ke AI
        $maliciousCount = $stats['malicious'];
        $harmlessCount = $stats['harmless'];
        $totalEngines = $maliciousCount + $harmlessCount + ($stats['suspicious'] ?? 0) + ($stats['timeout'] ?? 0);

        $technicalReport = "Nama File: {$originalName}. Tipe: Aplikasi/Dokumen. Hasil scan HASH: Terdeteksi VIRUS/BAHAYA oleh {$maliciousCount} dari total {$totalEngines} antivirus.";

        $aiResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'HTTP-Referer' => env('APP_URL', 'http://localhost'),
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->retry(3, 2000)
        ->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'nex-agi/nex-n2-pro:free', 
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten keamanan siber yang membantu masyarakat umum terhindar dari ancaman digital (APK palsu, dokumen berbahaya, virus). Ubah laporan keamanan file menjadi penjelasan Bahasa Indonesia yang lugas, profesional, namun mudah dipahami orang awam. PENTING: Gunakan gaya bahasa universal yang netral. DILARANG KERAS menggunakan sapaan spesifik seperti "Bapak", "Ibu", "Kakak", atau "Keluarga". WAJIB gunakan format teks WhatsApp untuk penekanan (contoh: gunakan *teks* untuk huruf tebal). DILARANG menggunakan markdown standar seperti **. Di bagian atas tulis status: [🔴 BAHAYA / JANGAN DIINSTAL] jika malicious > 0, atau [🟢 AMAN] jika 0. Jelaskan risiko konkret jika file tersebut diinstal atau dibuka.'
                ],
                [
                    'role' => 'user',
                    'content' => "Tolong buatkan penjelasan untuk file ini: {$technicalReport}"
                ]
            ]
        ]);

        $aiExplanation = "Penjelasan AI tidak tersedia, namun sistem mendeteksi indikasi pada file ini.";
        if ($aiResponse->successful()) {
            $aiExplanation = $aiResponse->json()['choices'][0]['message']['content'];
        }

        // 5. Simpan & Kembalikan Respons
        $newScan = ScanHistory::create([
            'type' => 'file',
            'input_value' => $originalName,
            'file_hash' => $fileHash,
            'malicious_count' => $maliciousCount,
            'total_engines' => $totalEngines,
            'ai_explanation' => $aiExplanation,
        ]);

        return response()->json([
            'status' => 'success',
            'source' => 'live_api_hash',
            'data' => $newScan
        ]);
    }
}