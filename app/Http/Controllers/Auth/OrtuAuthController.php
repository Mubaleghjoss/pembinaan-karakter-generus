<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrtuAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('ortu')->check()) {
            return redirect()->route('ortu.dashboard');
        }

        return response()
            ->view('auth.ortu-login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find siswa by ortu_username
        $siswa = Siswa::where('ortu_username', $request->username)->first();

        if (!$siswa) {
            return redirect()->route('ortu.login', ['error' => 'Username tidak ditemukan.'])
                ->withErrors(['username' => 'Username tidak ditemukan.'])
                ->withInput($request->only('username'));
        }

        if (!$siswa->isActive()) {
            return redirect()->route('ortu.login', ['error' => 'Akun tidak aktif. Hubungi admin.'])
                ->withErrors(['username' => 'Akun tidak aktif. Hubungi admin.'])
                ->withInput($request->only('username'));
        }

        // Check ortu_password — ortu has independent credentials from siswa
        // If ortu_password not set yet, initialize it to NIS (default)
        if (!$siswa->ortu_password) {
            $siswa->ortu_password = Hash::make($siswa->nis);
            $siswa->ortu_password_plain = $siswa->nis;
            $siswa->save();
        }
        if (!Hash::check($request->password, $siswa->ortu_password)) {
            return redirect()->route('ortu.login', ['error' => 'Password salah.'])
                ->withErrors(['password' => 'Password salah.'])
                ->withInput($request->only('username'));
        }

        Auth::guard('ortu')->login($siswa, $request->filled('remember'));

        $siswa->update(['ortu_last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('ortu.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('ortu')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('ortu.login', ['fresh' => Str::random(12)])
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }
}
