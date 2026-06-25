<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

/**
 * Logger khusus untuk event autentikasi
 *
 * Class ini menangani logging untuk semua event terkait login,
 * logout, dan authentication attempts.
 */
class AuthLogger
{
    protected const CHANNEL = 'auth';

    /**
     * Log login attempt
     *
     * @param  string  $username  Username yang mencoba login
     * @param  bool  $success  Apakah login berhasil
     * @param  string|null  $ipAddress  IP address
     * @param  array  $additionalData  Data tambahan
     */
    public static function logLoginAttempt(
        string $username,
        bool $success,
        ?string $ipAddress = null,
        array $additionalData = []
    ): void {
        $data = PiiMasker::mask([
            'event' => 'login_attempt',
            'username' => $username,
            'success' => $success,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
            'user_agent' => $additionalData['user_agent'] ?? null,
            ...$additionalData,
        ]);

        $message = sprintf(
            'Login attempt: username=%s, success=%s, ip=%s',
            $username,
            $success ? 'true' : 'false',
            $ipAddress ?? 'unknown'
        );

        if ($success) {
            self::info($message, $data);
        } else {
            self::warning($message, $data);
        }
    }

    /**
     * Log successful login
     */
    public static function logLogin(
        int $userId,
        string $username,
        ?string $ipAddress = null
    ): void {
        $data = [
            'event' => 'login_success',
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
        ];

        self::info(
            sprintf('User logged in: user_id=%d, username=%s, ip=%s', $userId, $username, $ipAddress ?? 'unknown'),
            $data
        );
    }

    /**
     * Log logout
     */
    public static function logLogout(
        int $userId,
        string $username,
        ?string $ipAddress = null
    ): void {
        $data = [
            'event' => 'logout',
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
        ];

        self::info(
            sprintf('User logged out: user_id=%d, username=%s, ip=%s', $userId, $username, $ipAddress ?? 'unknown'),
            $data
        );
    }

    /**
     * Log failed login (multiple attempts)
     */
    public static function logFailedLogin(
        string $username,
        int $attemptCount,
        ?string $ipAddress = null
    ): void {
        $data = [
            'event' => 'login_failed',
            'username' => $username,
            'attempt_count' => $attemptCount,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
        ];

        self::warning(
            sprintf('Failed login: username=%s, attempts=%d, ip=%s', $username, $attemptCount, $ipAddress ?? 'unknown'),
            $data
        );
    }

    /**
     * Log account lockout
     */
    public static function logLockout(
        string $username,
        ?string $ipAddress = null,
        int $lockoutMinutes = 15
    ): void {
        $data = [
            'event' => 'account_lockout',
            'username' => $username,
            'ip_address' => $ipAddress,
            'lockout_minutes' => $lockoutMinutes,
            'timestamp' => now()->toISOString(),
        ];

        self::warning(
            sprintf('Account locked: username=%s, ip=%s, duration=%d minutes', $username, $ipAddress ?? 'unknown', $lockoutMinutes),
            $data
        );
    }

    /**
     * Log password change
     */
    public static function logPasswordChange(
        int $userId,
        string $username,
        ?string $ipAddress = null
    ): void {
        $data = [
            'event' => 'password_changed',
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
        ];

        self::info(
            sprintf('Password changed: user_id=%d, username=%s, ip=%s', $userId, $username, $ipAddress ?? 'unknown'),
            $data
        );
    }

    /**
     * Log biometric-related event.
     */
    public static function logBiometricEvent(
        string $action,
        ?int $userId = null,
        ?string $username = null,
        ?string $ipAddress = null,
        array $additionalData = []
    ): void {
        $data = PiiMasker::mask([
            'event' => 'biometric_' . $action,
            'action' => $action,
            'user_id' => $userId,
            'username' => $username,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
            ...$additionalData,
        ]);

        $message = sprintf(
            'Biometric event: action=%s, user_id=%s, username=%s, ip=%s',
            $action,
            $userId !== null ? (string) $userId : 'unknown',
            $username ?? 'unknown',
            $ipAddress ?? 'unknown'
        );

        if (in_array($action, ['login_failed', 'register_failed', 'delete_failed'], true)) {
            self::warning($message, $data);
            return;
        }

        self::info($message, $data);
    }

    protected static function info(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->info($message, $context);
    }

    protected static function warning(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->warning($message, $context);
    }

    protected static function error(string $message, array $context = []): void
    {
        Log::channel(self::CHANNEL)->error($message, $context);
    }
}
