<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

/**
 * Logger khusus untuk event presensi/kehadiran
 *
 * Class ini menangani logging untuk semua event terkait QR scan
 * dan presensi dengan format yang konsisten.
 */
class AttendanceLogger
{
    protected const CHANNEL = 'attendance';

    /**
     * Log QR scan attempt
     *
     * @param  int|null  $studentId  ID siswa (null jika tidak ditemukan)
     * @param  string  $result  Hasil scan (success, failed, expired, invalid)
     * @param  string|null  $ipAddress  IP address scanner
     * @param  array  $additionalData  Data tambahan
     */
    public static function logScan(
        ?int $studentId,
        string $result,
        ?string $ipAddress = null,
        array $additionalData = []
    ): void {
        $data = PiiMasker::mask([
            'event' => 'qr_scan',
            'student_id' => $studentId,
            'result' => $result,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
            ...$additionalData,
        ]);

        $message = sprintf(
            'QR Scan: student_id=%s, result=%s, ip=%s',
            $studentId ?? 'unknown',
            $result,
            $ipAddress ?? 'unknown'
        );

        if ($result === 'success') {
            self::info($message, $data);
        } else {
            self::warning($message, $data);
        }
    }

    /**
     * Log successful attendance record
     */
    public static function logAttendanceRecorded(
        int $studentId,
        string $status,
        string $method = 'qr_scan'
    ): void {
        $data = [
            'event' => 'attendance_recorded',
            'student_id' => $studentId,
            'status' => $status,
            'method' => $method,
            'timestamp' => now()->toISOString(),
        ];

        self::info(
            sprintf('Attendance recorded: student_id=%d, status=%s, method=%s', $studentId, $status, $method),
            $data
        );
    }

    /**
     * Log attendance verification
     */
    public static function logVerification(
        int $presensiId,
        int $verifierId
    ): void {
        $data = [
            'event' => 'attendance_verified',
            'presensi_id' => $presensiId,
            'verifier_id' => $verifierId,
            'timestamp' => now()->toISOString(),
        ];

        self::info(
            sprintf('Attendance verified: presensi_id=%d, verifier_id=%d', $presensiId, $verifierId),
            $data
        );
    }

    /**
     * Log duplicate attendance attempt
     */
    public static function logDuplicateAttempt(
        int $studentId,
        string $date
    ): void {
        $data = [
            'event' => 'duplicate_attendance_attempt',
            'student_id' => $studentId,
            'date' => $date,
            'timestamp' => now()->toISOString(),
        ];

        self::warning(
            sprintf('Duplicate attendance attempt: student_id=%d, date=%s', $studentId, $date),
            $data
        );
    }

    /**
     * Log expired QR token
     */
    public static function logExpiredToken(
        int $studentId,
        ?string $ipAddress = null
    ): void {
        $data = [
            'event' => 'qr_token_expired',
            'student_id' => $studentId,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
        ];

        self::warning(
            sprintf('Expired QR token: student_id=%d, ip=%s', $studentId, $ipAddress ?? 'unknown'),
            $data
        );
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
