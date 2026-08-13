<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider untuk konfigurasi Rate Limiting
 *
 * Provider ini mengkonfigurasi rate limiter untuk berbagai endpoint API
 * termasuk QR scan, QR generate, dan authentication.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // QR scan endpoint rate limiting
        RateLimiter::for('qr-scan', function (Request $request) {
            $limit = config('qrcode.rate_limit.scan_per_minute', 30);

            return Limit::perMinute($limit)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many requests',
                        'message' => 'Terlalu banyak permintaan scan QR. Silakan coba lagi nanti.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429, $headers);
                });
        });

        // QR generate endpoint rate limiting
        RateLimiter::for('qr-generate', function (Request $request) {
            $limit = config('qrcode.rate_limit.generate_per_minute', 10);

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many requests',
                        'message' => 'Terlalu banyak permintaan generate QR. Silakan coba lagi nanti.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429, $headers);
                });
        });

        // Public RPG pages stay open to guests, but requests are bounded per IP.
        RateLimiter::for('rpg-public', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('rpg-presence', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak update kehadiran game. Silakan lanjut bermain, status online akan diperbarui setelah jeda.',
                    ], 429, $headers);
                });
        });

        // Authentication endpoint rate limiting
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Too many requests',
                        'message' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ], 429, $headers);
                });
        });

        RateLimiter::for('biometric', function (Request $request) {
            return [
                Limit::perMinute(10)->by('biometric-minute:'.$request->ip()),
                Limit::perHour(60)->by('biometric-hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('csp-report', function (Request $request) {
            return Limit::perMinute(20)->by('csp:'.$request->ip());
        });

        RateLimiter::for('quran-public-scan', function (Request $request) {
            $payload = trim((string) $request->input('sheet_payload', ''));
            $scan = $request->route('scan');
            $sheetKey = $payload !== ''
                ? hash('sha256', mb_substr($payload, 0, 500))
                : 'scan:'.(is_object($scan) ? ($scan->sheet_id ?? $scan->id ?? 'unknown') : ($scan ?: 'unknown'));

            return [
                Limit::perMinutes(10, 5)->by('quran-public-ip:'.$request->ip()),
                Limit::perHour(3)->by('quran-public-sheet:'.$sheetKey),
            ];
        });
    }
}
