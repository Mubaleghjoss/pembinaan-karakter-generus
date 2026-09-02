<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesSiswaToken;
use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Jembatan satu-kali dari token Sanctum (aplikasi mobile) ke sesi web.
 *
 * Latar belakang: sebagian fitur server (chat, biometrik, jurnal RPP, lembar
 * Quran lanjutan, dll.) hanya punya halaman web ber-sesi — tidak ada endpoint
 * API v1-nya. Supaya menu di aplikasi benar-benar bisa dipakai (bukan hanya
 * menampilkan ringkasan), aplikasi menukar token Sanctum-nya dengan satu URL
 * berumur pendek; URL itu dibuka di WebView, membuat sesi web untuk akun yang
 * sama, lalu langsung diarahkan ke halaman tujuan.
 *
 * Pembatas yang disengaja:
 * - hanya bisa diminta oleh pemilik token Sanctum yang sah (`auth:sanctum`);
 * - `target` wajib ada di allowlist {@see MobileServerFeaturesController::webTargets()};
 * - token bridge sekali pakai (di-`pull` dari cache) dan hanya berlaku 120 detik;
 * - token disimpan sebagai hash SHA-256, bukan nilai mentah;
 * - guard sesi mengikuti tipe akun (siswa / ortu / web) sehingga tidak ada
 *   kenaikan hak akses.
 */
class MobileWebBridgeController extends Controller
{
    use ResolvesSiswaToken;

    /** Umur token bridge (detik). Sengaja pendek: sekali pakai, langsung dibuka. */
    public const TTL_DETIK = 120;

    private const PREFIX = 'mobile-web-bridge:';

    /**
     * Tukar token Sanctum dengan URL sesi web sekali pakai.
     */
    public function issue(Request $request): JsonResponse
    {
        $user = $request->user();
        $target = $this->normalizeTarget((string) $request->input('target', ''));

        if ($target === null) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter target wajib berupa path relatif, contoh /siswa/chat.',
                'code' => 'TARGET_INVALID',
            ], 422);
        }

        $tipe = $this->tipeAktor($request, $user);
        $izin = MobileServerFeaturesController::webTargets($tipe);

        if (! in_array($target, $izin, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman "'.$target.'" tidak tersedia untuk akun ini.',
                'code' => 'TARGET_NOT_ALLOWED',
                'meta' => ['tersedia' => $izin],
            ], 403);
        }

        $token = Str::random(64);

        Cache::put(
            self::PREFIX.hash('sha256', $token),
            [
                'guard' => $this->guardUntuk($tipe),
                'id' => $user->getKey(),
                'target' => $target,
            ],
            now()->addSeconds(self::TTL_DETIK)
        );

        return response()->json([
            'success' => true,
            'data' => [
                // Path relatif: aplikasi menempelkan base URL-nya sendiri
                // (PKG_API_BASE), karena APP_URL server belum tentu sama
                // dengan alamat yang dipakai perangkat.
                'path' => '/mobile-bridge/'.$token,
                'url' => url('/mobile-bridge/'.$token),
                'target' => $target,
                'expires_in' => self::TTL_DETIK,
            ],
        ]);
    }

    /**
     * Konsumsi token bridge: buat sesi web lalu arahkan ke halaman tujuan.
     */
    public function consume(Request $request, string $token): RedirectResponse
    {
        $payload = Cache::pull(self::PREFIX.hash('sha256', $token));

        if (! is_array($payload)) {
            abort(410, 'Tautan sesi sudah dipakai atau kedaluwarsa. Buka ulang menu dari aplikasi.');
        }

        $guard = (string) ($payload['guard'] ?? '');
        $target = $this->normalizeTarget((string) ($payload['target'] ?? ''));

        if ($target === null || ! in_array($guard, ['siswa', 'ortu', 'web'], true)) {
            abort(410, 'Tautan sesi tidak valid.');
        }

        $akun = $guard === 'web'
            ? User::find($payload['id'] ?? null)
            : Siswa::find($payload['id'] ?? null);

        if ($akun === null) {
            abort(410, 'Akun tidak ditemukan lagi.');
        }

        if ($akun instanceof Siswa && ! $akun->canLogin()) {
            abort(403, 'Akun tidak aktif. Hubungi Admin.');
        }

        Auth::guard($guard)->login($akun);
        $request->session()->regenerate();

        return redirect($target);
    }

    private function tipeAktor(Request $request, mixed $user): string
    {
        if ($user instanceof Siswa) {
            return $this->tokenHasAbility($request, 'ortu') ? 'ortu' : 'siswa';
        }

        return 'staff';
    }

    private function guardUntuk(string $tipe): string
    {
        return match ($tipe) {
            'siswa' => 'siswa',
            'ortu' => 'ortu',
            default => 'web',
        };
    }

    /**
     * Hanya izinkan path relatif satu tingkat (tanpa skema/host), supaya token
     * bridge tidak bisa dipakai sebagai open redirect.
     */
    private function normalizeTarget(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '' || str_contains($raw, '://') || str_starts_with($raw, '//')) {
            return null;
        }

        $path = '/'.ltrim($raw, '/');

        return preg_match('#^/[A-Za-z0-9\-_/]*$#', $path) === 1 ? $path : null;
    }
}
