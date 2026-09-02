<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API v1 — login siswa & orang tua untuk aplikasi mobile.
 *
 * Kontrak kredensial disamakan dengan alur web yang sudah ada
 * (app/Http/Controllers/Auth/SiswaAuthController.php dan OrtuAuthController.php):
 *   - siswa : NIS  + password       (kolom `nis`, `password`)
 *   - ortu  : username + password   (kolom `ortu_username`, `ortu_password`)
 *
 * Sama seperti web, password yang belum pernah diset akan diinisialisasi
 * ke NIS pada percobaan login pertama supaya akun lama tetap bisa masuk.
 * Perbedaannya: di sini yang dikeluarkan adalah token Sanctum, bukan sesi.
 *
 * Ability token membedakan peran: 'siswa' vs 'ortu'.
 */
class SiswaAuthController extends Controller
{
    /**
     * Login siswa (NIS + password).
     */
    public function loginSiswa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nis' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $siswa = Siswa::where('nis', $data['nis'])->first();

        if (! $siswa) {
            throw ValidationException::withMessages([
                'nis' => ['NIS atau password salah'],
            ]);
        }

        // Selaras dengan web: password kosong diinisialisasi ke NIS.
        if (! $siswa->password) {
            $siswa->password = $siswa->nis;
            $siswa->save();
        }

        if (! Hash::check($data['password'], $siswa->password)) {
            throw ValidationException::withMessages([
                'nis' => ['NIS atau password salah'],
            ]);
        }

        return $this->issueToken($siswa, 'siswa');
    }

    /**
     * Login orang tua (ortu_username + ortu_password).
     */
    public function loginOrtu(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $siswa = Siswa::where('ortu_username', $data['username'])->first();

        if (! $siswa) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password orang tua salah'],
            ]);
        }

        // Selaras dengan web: kredensial ortu default = NIS.
        if (! $siswa->ortu_password) {
            $siswa->ortu_password = $siswa->nis;
            $siswa->save();
        }

        if (! Hash::check($data['password'], $siswa->ortu_password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password orang tua salah'],
            ]);
        }

        return $this->issueToken($siswa, 'ortu');
    }

    /**
     * Profil akun siswa/ortu yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $siswa = $request->user();

        if (! $siswa instanceof Siswa) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Token ini bukan token siswa/orang tua',
                'code' => 'NOT_SISWA_TOKEN',
            ], 403);
        }

        $role = $siswa->currentAccessToken()?->can('ortu') ? 'ortu' : 'siswa';

        return response()->json([
            'success' => true,
            'data' => $this->profile($siswa, $role),
        ]);
    }

    /**
     * Cabut token yang sedang dipakai.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil keluar',
        ]);
    }

    /**
     * Buat token dengan ability sesuai peran.
     */
    private function issueToken(Siswa $siswa, string $role): JsonResponse
    {
        if (! $siswa->canLogin()) {
            return response()->json([
                'success' => false,
                'error' => 'Account inactive',
                'message' => 'Akun tidak aktif. Hubungi pengurus.',
                'code' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        $token = $siswa
            ->createToken($role.'-mobile', [$role], Carbon::now()->addDays(7))
            ->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in_days' => 7,
                'user' => $this->profile($siswa, $role),
            ],
        ]);
    }

    /**
     * Bentuk profil yang dipakai app (tanpa kolom sensitif).
     */
    private function profile(Siswa $siswa, string $role): array
    {
        return [
            'id' => $siswa->id,
            'role' => $role,
            'nama' => $siswa->nama,
            'nis' => $siswa->nis,
            'kelas' => $siswa->kelas?->nama,
            'kelompok' => $siswa->kelompok,
            'school_grade' => $siswa->school_grade,
            'status' => $siswa->status,
            'is_graduated' => $siswa->isGraduated(),
            // `canSubmitAsAlumni()` hanya menilai status alumni, bukan peran.
            // Untuk token ortu jawabannya selalu false: ortu read-only
            // (endpoint submit menolak dengan ORTU_READ_ONLY), jadi jangan
            // kirim true dan membuat UI menampilkan tombol yang pasti gagal.
            'can_submit' => $role !== 'ortu' && $siswa->canSubmitAsAlumni(),
            'nama_wali' => $role === 'ortu' ? $siswa->nama_wali : null,
        ];
    }
}
