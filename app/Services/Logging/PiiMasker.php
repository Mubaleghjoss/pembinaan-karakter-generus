<?php

namespace App\Services\Logging;

/**
 * Utility untuk masking PII (Personally Identifiable Information) dalam log
 *
 * Class ini menyediakan method untuk menyembunyikan data sensitif
 * seperti email, phone, alamat sebelum di-log.
 */
class PiiMasker
{
    /**
     * Field yang dianggap PII dan perlu di-mask
     */
    protected static array $piiFields = [
        'email',
        'email_wali',
        'phone',
        'phone_wali',
        'alamat',
        'password',
        'token',
        'qr_token',
        'qr_secret_salt',
    ];

    /**
     * Mask data PII dalam array
     *
     * @param  array  $data  Data yang akan di-mask
     * @return array Data dengan PII yang sudah di-mask
     */
    public static function mask(array $data): array
    {
        return self::maskRecursive($data);
    }

    /**
     * Mask data secara rekursif
     */
    protected static function maskRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::maskRecursive($value);
            } elseif (self::isPiiField($key)) {
                $data[$key] = self::maskValue($key, $value);
            }
        }

        return $data;
    }

    /**
     * Cek apakah field adalah PII
     */
    protected static function isPiiField(string $key): bool
    {
        return in_array(strtolower($key), self::$piiFields);
    }

    /**
     * Mask value berdasarkan tipe field
     */
    protected static function maskValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '[empty]';
        }

        $key = strtolower($key);

        return match (true) {
            str_contains($key, 'email') => self::maskEmail((string) $value),
            str_contains($key, 'phone') => self::maskPhone((string) $value),
            str_contains($key, 'alamat') => self::maskAddress((string) $value),
            str_contains($key, 'password') => '[REDACTED]',
            str_contains($key, 'token') => self::maskToken((string) $value),
            str_contains($key, 'salt') => '[REDACTED]',
            default => self::maskGeneric((string) $value),
        };
    }

    /**
     * Mask email address
     *
     * example@domain.com -> ex***@do***.com
     */
    public static function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return self::maskGeneric($email);
        }

        [$local, $domain] = explode('@', $email);
        $maskedLocal = substr($local, 0, 2).'***';

        $domainParts = explode('.', $domain);
        $maskedDomain = substr($domainParts[0], 0, 2).'***';

        if (count($domainParts) > 1) {
            $maskedDomain .= '.'.end($domainParts);
        }

        return $maskedLocal.'@'.$maskedDomain;
    }

    /**
     * Mask phone number
     * 081234567890 -> 0812****7890
     */
    public static function maskPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $length = strlen($phone);

        if ($length < 8) {
            return '****';
        }

        return substr($phone, 0, 4).'****'.substr($phone, -4);
    }

    /**
     * Mask address
     * Jl. Merdeka No. 123 -> Jl. Me*** [MASKED]
     */
    public static function maskAddress(string $address): string
    {
        $words = explode(' ', $address);

        if (count($words) <= 2) {
            return '[MASKED ADDRESS]';
        }

        return $words[0].' '.substr($words[1], 0, 2).'*** [MASKED]';
    }

    /**
     * Mask token
     * abc123def456 -> abc1****f456
     */
    public static function maskToken(string $token): string
    {
        $length = strlen($token);

        if ($length < 8) {
            return '****';
        }

        return substr($token, 0, 4).'****'.substr($token, -4);
    }

    /**
     * Generic masking
     */
    public static function maskGeneric(string $value): string
    {
        $length = strlen($value);

        if ($length <= 4) {
            return '****';
        }

        return substr($value, 0, 2).'***'.substr($value, -2);
    }

    /**
     * Tambah field PII custom
     */
    public static function addPiiField(string $field): void
    {
        if (! in_array(strtolower($field), self::$piiFields)) {
            self::$piiFields[] = strtolower($field);
        }
    }

    /**
     * Get daftar field PII
     */
    public static function getPiiFields(): array
    {
        return self::$piiFields;
    }
}
