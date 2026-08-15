<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\Auth\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Controller for siswa authentication.
 * 
 * **Feature: website-settings, Requirements 9.1, 9.2, 9.3, 9.4**
 */
class SiswaAuthController extends Controller
{
    public function __construct(private readonly LoginThrottle $loginThrottle)
    {
    }
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
        
        return response()
            ->view('auth.siswa-login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
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

        $identity = (string) $request->input('nis');
        $this->loginThrottle->ensureNotLimited($request, 'siswa', $identity, 'nis');

        $siswa = Siswa::where('nis', $request->nis)->first();

        if (! $siswa || ! $siswa->canLogin() || ! Hash::check($request->password, $siswa->password)) {
            $this->loginThrottle->recordFailure($request, 'siswa', $identity);

            return redirect()->route('siswa.login')
                ->withErrors(['nis' => 'NIS atau password salah.'])
                ->withInput($request->only('nis'));
        }

        Auth::guard('siswa')->login($siswa, $request->filled('remember'));

        // Update last login timestamp
        $siswa->update(['last_login_at' => now()]);

        $request->session()->regenerate();
        $this->loginThrottle->clearIdentity($request, 'siswa', $identity);

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

        return redirect()
            ->route('siswa.login', ['fresh' => Str::random(12)])
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }
}
