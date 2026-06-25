<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Update last_login_at for authenticated users once per day.
 * 
 * This fixes the issue where pamong who stay logged in via session/remember-me
 * never trigger the recordLogin() method, causing stale last_login_at values.
 */
class UpdateLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $this->shouldUpdate($user)) {
            $user->updateQuietly([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Cache the update so we don't hit DB on every single request
            session(['last_activity_updated' => now()->toDateString()]);
        }

        return $next($request);
    }

    /**
     * Only update once per day per session to avoid excessive DB writes.
     */
    protected function shouldUpdate($user): bool
    {
        $lastUpdated = session('last_activity_updated');

        // Update if never tracked in this session, or if it's a new day
        return !$lastUpdated || $lastUpdated !== now()->toDateString();
    }
}
