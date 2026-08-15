<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOrtu
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('ortu')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('ortu.login');
        }

        if (! Auth::guard('ortu')->user()->canLogin()) {
            Auth::guard('ortu')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('ortu.login')
                ->withErrors(['username' => 'Akun tidak aktif. Hubungi Admin.']);
        }

        return $next($request);
    }
}
