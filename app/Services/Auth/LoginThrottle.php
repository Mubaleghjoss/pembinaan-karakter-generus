<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginThrottle
{
    public const MAX_IDENTITY_ATTEMPTS = 5;

    public const MAX_IP_ATTEMPTS = 25;

    public const DECAY_SECONDS = 300;

    public function ensureNotLimited(Request $request, string $realm, string $identity, string $field): void
    {
        $identityKey = $this->identityKey($request, $realm, $identity);
        $ipKey = $this->ipKey($request, $realm);

        if (! RateLimiter::tooManyAttempts($identityKey, self::MAX_IDENTITY_ATTEMPTS)
            && ! RateLimiter::tooManyAttempts($ipKey, self::MAX_IP_ATTEMPTS)) {
            return;
        }

        $seconds = max(
            RateLimiter::availableIn($identityKey),
            RateLimiter::availableIn($ipKey),
        );

        throw ValidationException::withMessages([
            $field => ["Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."],
        ]);
    }

    public function recordFailure(Request $request, string $realm, string $identity): void
    {
        RateLimiter::hit($this->identityKey($request, $realm, $identity), self::DECAY_SECONDS);
        RateLimiter::hit($this->ipKey($request, $realm), self::DECAY_SECONDS);
    }

    public function clearIdentity(Request $request, string $realm, string $identity): void
    {
        RateLimiter::clear($this->identityKey($request, $realm, $identity));
    }

    private function identityKey(Request $request, string $realm, string $identity): string
    {
        $normalized = Str::lower(trim($identity));

        return 'login:'.$realm.':identity:'.sha1($normalized.'|'.$request->ip());
    }

    private function ipKey(Request $request, string $realm): string
    {
        return 'login:'.$realm.':ip:'.sha1((string) $request->ip());
    }
}
