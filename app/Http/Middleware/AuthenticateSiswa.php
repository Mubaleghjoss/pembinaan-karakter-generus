<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSiswa
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('siswa')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            // Preserve the requested student page so public InkWave entry can
            // continue to its map picker after a successful login.
            return redirect()->guest(route('siswa.login'));
        }

        if (! Auth::guard('siswa')->user()->canLogin()) {
            Auth::guard('siswa')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('siswa.login')
                ->withErrors(['nis' => 'Akun tidak aktif. Hubungi Admin.']);
        }

        return $next($request);
    }
}
