<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Helper bersama untuk endpoint API yang harus dijalankan sebagai
 * siswa atau orang tua (token Sanctum milik model Siswa).
 *
 * Alasan dibuat terpisah: middleware `role.permission` hanya mengerti
 * model User (punya relasi role). Token milik Siswa harus dicek dengan
 * cara lain, yaitu memeriksa instance model + ability pada token.
 */
trait ResolvesSiswaToken
{
    /**
     * Ambil Siswa dari token; null bila token bukan milik siswa/ortu.
     */
    protected function siswaFromToken(Request $request): ?Siswa
    {
        $user = $request->user();

        return $user instanceof Siswa ? $user : null;
    }

    /**
     * Respons 403 standar bila token bukan token siswa/ortu.
     */
    protected function forbiddenNonSiswa(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Endpoint ini hanya untuk akun siswa atau orang tua',
            'code' => 'NOT_SISWA_TOKEN',
        ], 403);
    }

    /**
     * Apakah token punya ability tertentu ('siswa' atau 'ortu').
     */
    protected function tokenHasAbility(Request $request, string $ability): bool
    {
        $token = $request->user()?->currentAccessToken();

        return $token !== null && $token->can($ability);
    }
}
