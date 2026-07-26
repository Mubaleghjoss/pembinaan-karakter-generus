<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class WhatsappLink
{
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return preg_match('/^62[0-9]{8,13}$/', $digits) ? $digits : null;
    }

    public static function normalizeOrFail(string $phone, string $field = 'whatsapp'): string
    {
        $normalized = self::normalize($phone);

        if (! $normalized) {
            throw ValidationException::withMessages([
                $field => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        return $normalized;
    }

    public static function url(?string $phone, string $message = ''): ?string
    {
        $normalized = self::normalize($phone);

        if (! $normalized) {
            return null;
        }

        return 'https://wa.me/'.$normalized.($message !== '' ? '?text='.rawurlencode($message) : '');
    }
}
