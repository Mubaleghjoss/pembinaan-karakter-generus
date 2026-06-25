<?php

namespace App\Support;

use App\Models\WebAuthnCredential;

class BiometricStatus
{
    public const ACTIVE = 'active';
    public const LEGACY = 'legacy';
    public const INACTIVE = 'inactive';

    public static function resolve(int $userId, string $userType): array
    {
        if (! WebAuthnCredential::supportsCredentialPublicKey()) {
            $legacyCount = WebAuthnCredential::query()
                ->where('user_id', $userId)
                ->where('user_type', $userType)
                ->count();

            return [
                'status' => self::fromCounts(0, $legacyCount),
                'has_valid_credential' => false,
                'valid_count' => 0,
                'legacy_count' => $legacyCount,
            ];
        }

        $counts = WebAuthnCredential::query()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->selectRaw('SUM(CASE WHEN credential_public_key IS NOT NULL THEN 1 ELSE 0 END) as valid_count')
            ->selectRaw('SUM(CASE WHEN credential_public_key IS NULL THEN 1 ELSE 0 END) as legacy_count')
            ->first();

        $validCount = (int) ($counts?->valid_count ?? 0);
        $legacyCount = (int) ($counts?->legacy_count ?? 0);

        return [
            'status' => self::fromCounts($validCount, $legacyCount),
            'has_valid_credential' => $validCount > 0,
            'valid_count' => $validCount,
            'legacy_count' => $legacyCount,
        ];
    }

    public static function fromCounts(int $validCount, int $legacyCount): string
    {
        if ($validCount > 0) {
            return self::ACTIVE;
        }

        if ($legacyCount > 0) {
            return self::LEGACY;
        }

        return self::INACTIVE;
    }
}
