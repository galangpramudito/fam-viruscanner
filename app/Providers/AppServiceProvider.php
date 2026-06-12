<?php

namespace App\Providers;

use App\Services\OpenRouterClient;
use App\Services\ScanService;
use App\Services\VirusTotalClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VirusTotalClient::class, fn () => VirusTotalClient::fromConfig());
        $this->app->singleton(OpenRouterClient::class, fn () => OpenRouterClient::fromConfig());
        $this->app->singleton(ScanService::class);
    }

    public function boot(): void
    {
        // Set panjang string default untuk menangani limitasi indeks Postgres/Neon
        Schema::defaultStringLength(191);

        RateLimiter::for('scan', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip())->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terlalu banyak permintaan. Silakan tunggu 1 menit.',
                ], 429);
            });
        });
    }
}