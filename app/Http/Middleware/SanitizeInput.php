<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk sanitasi input
 *
 * Middleware ini membersihkan input dari karakter berbahaya
 * seperti HTML tags dan script injection.
 */
class SanitizeInput
{
    /**
     * Fields yang tidak perlu di-sanitize (seperti password, rich text)
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $sanitized = $this->sanitizeArray($input);
        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Sanitize array secara rekursif
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize string value
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Strip HTML tags
        $value = strip_tags($value);

        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Remove potential SQL injection patterns
        $value = $this->removeSqlPatterns($value);

        // Trim whitespace
        $value = trim($value);

        return $value;
    }

    /**
     * Remove common SQL injection patterns
     */
    protected function removeSqlPatterns(string $value): string
    {
        $patterns = [
            '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',
            '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',
            '/\w*((\%27)|(\'))((\%6F)|o|(\%4F))((\%72)|r|(\%52))/i',
            '/((\%27)|(\'))union/i',
        ];

        foreach ($patterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }

        return $value;
    }

    /**
     * Add field to exception list
     */
    public function addException(string $field): void
    {
        if (! in_array($field, $this->except)) {
            $this->except[] = $field;
        }
    }
}
