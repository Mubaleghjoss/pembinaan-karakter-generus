<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controller for siswa authentication.
 * 
 * **Feature: website-settings, Requirements 9.1, 9.2, 9.3, 9.4**
 */
class SiswaAuthController extends Controller
{
    /**
     * Show the siswa login form.
     * 
     * **Validates: Requirements 9.1**
     */
    public function showLoginForm()
    {
        if (Auth::guard('siswa')->check()) {
            return redirect()->route('siswa.dashboard');
        }
        
        return view('auth.siswa-login');
    }

    /**
     * Handle siswa login request.
     * 
     * **Validates: Requirements 9.1, 9.4**
     */
    public function login(Request $request)
    {
        $request->validate([
            'nis' => 'required|string',
            'password' => 'required|string',
        ]);

        $siswa = Siswa::where('nis', $request->nis)->first();

        if (!$siswa) {
            return redirect()->route('siswa.login', ['error' => 'NIS tidak ditemukan.'])
                ->withErrors(['nis' => 'NIS tidak ditemukan.'])
                ->withInput($request->only('nis'));
        }

        if (!$siswa->isActive()) {
            return redirect()->route('siswa.login', ['error' => 'Akun siswa tidak aktif. Hubungi admin.'])
                ->withErrors(['nis' => 'Akun siswa tidak aktif. Hubungi admin.'])
                ->withInput($request->only('nis'));
        }

        if (!Hash::check($request->password, $siswa->password)) {
            return redirect()->route('siswa.login', ['error' => 'Password salah.'])
                ->withErrors(['password' => 'Password salah.'])
                ->withInput($request->only('nis'));
        }

        Auth::guard('siswa')->login($siswa, $request->filled('remember'));

        // Update last login timestamp
        $siswa->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->intended(route('siswa.dashboard'));
    }

    /**
     * Handle siswa logout request.
     * 
     * **Validates: Requirements 9.2**
     */
    public function logout(Request $request)
    {
        Auth::guard('siswa')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('siswa.login');
    }
}
