<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\Auth\LoginThrottle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrtuAuthController extends Controller
{
    public function __construct(private readonly LoginThrottle $loginThrottle)
    {
    }
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

        $identity = (string) $request->input('username');
        $this->loginThrottle->ensureNotLimited($request, 'orang-tua', $identity, 'username');

        // Find siswa by ortu_username
        $siswa = Siswa::where('ortu_username', $request->username)->first();

        if (! $siswa || ! $siswa->isActive()) {
            $this->loginThrottle->recordFailure($request, 'orang-tua', $identity);

            return redirect()->route('ortu.login', ['error' => 'Data login tidak cocok atau akun tidak dapat digunakan.'])
                ->withErrors(['username' => 'Data login tidak cocok atau akun tidak dapat digunakan.'])
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
            $this->loginThrottle->recordFailure($request, 'orang-tua', $identity);

            return redirect()->route('ortu.login', ['error' => 'Data login tidak cocok atau akun tidak dapat digunakan.'])
                ->withErrors(['username' => 'Data login tidak cocok atau akun tidak dapat digunakan.'])
                ->withInput($request->only('username'));
        }

        Auth::guard('ortu')->login($siswa, $request->filled('remember'));

        $siswa->update(['ortu_last_login_at' => now()]);

        $request->session()->regenerate();
        $this->loginThrottle->clearIdentity($request, 'orang-tua', $identity);

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
