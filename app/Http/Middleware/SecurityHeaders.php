<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // Content Security Policy
        $csp = "default-src 'self'; ".
               "base-uri 'self'; ".
               "object-src 'none'; ".
               "form-action 'self'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ".
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
               "font-src 'self' blob: https://fonts.gstatic.com; ".
               "img-src 'self' data: blob: https:; ".
               "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://drive.google.com https://docs.google.com; ".
               "worker-src 'self' blob:; ".
               "connect-src 'self'; ".
               "frame-ancestors 'none';";

        $cspHeader = (config('app.debug') || app()->environment('local'))
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($cspHeader, $csp);

        // HSTS for HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
