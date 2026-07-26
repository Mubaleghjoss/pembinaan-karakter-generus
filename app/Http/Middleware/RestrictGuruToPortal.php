<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictGuruToPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isGuru()) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        $allowed = str_starts_with($routeName, 'guru.')
            || str_starts_with($routeName, 'pwa.')
            || str_starts_with($routeName, 'webauthn.')
            || in_array($routeName, ['logout', 'biometrik', 'manifest', 'guru.manifest'], true);

        if ($allowed) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->route(
                $user->must_change_password ? 'guru.password.initial' : 'guru.dashboard'
            );
        }

        abort(403, 'Akun Guru hanya dapat menggunakan Portal Guru.');
    }
}
