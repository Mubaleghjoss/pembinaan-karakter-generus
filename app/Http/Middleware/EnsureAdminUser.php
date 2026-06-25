<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya admin yang dapat mengakses fitur ini.',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Hanya admin yang dapat mengakses fitur ini.');
        }

        return $next($request);
    }
}
