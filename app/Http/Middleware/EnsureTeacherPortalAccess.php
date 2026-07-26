<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->loadMissing('role', 'teacherProfile');
        abort_unless($user, 401);
        abort_unless(
            $user->hasAnyRole([
                User::ROLE_ADMIN,
                User::ROLE_TEACHER,
                User::ROLE_PKG_MANAGER,
                User::ROLE_GURU,
            ]) && $user->teacherProfile?->is_active,
            403,
            'Akun belum ditautkan ke profil Guru aktif.'
        );

        return $next($request);
    }
}
