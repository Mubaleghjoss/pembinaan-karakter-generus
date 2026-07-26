<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isGuru() && $request->user()->must_change_password) {
            return redirect()->route('guru.password.initial');
        }

        return $next($request);
    }
}
