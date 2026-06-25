<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPamongPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menu, ?string $operation = null): Response
    {
        $user = $request->user();
        
        // If not authenticated, let other middleware handle it
        if (!$user) {
            return $next($request);
        }
        
        // Admin always has full access
        if ($user->isAdmin()) {
            return $next($request);
        }
        
        // Only apply to scoped operational roles
        if (! $user->usesPamongPermissionSystem()) {
            return $next($request);
        }
        
        // Check menu access
        if (!$user->hasPamongMenuAccess($menu)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke menu ini.',
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'Anda tidak memiliki akses ke menu ini.');
        }
        
        // Check CRUD operation if specified
        if ($operation && !$user->hasPamongCrudPermission($menu, $operation)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk operasi ini.',
                ], 403);
            }
            
            return redirect()->back()
                ->with('error', 'Anda tidak memiliki izin untuk operasi ini.');
        }
        
        return $next($request);
    }
}
