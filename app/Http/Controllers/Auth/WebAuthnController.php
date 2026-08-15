<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WebAuthnCredential;
use App\Services\Logging\AuthLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

class WebAuthnController extends Controller
{
    /**
     * Resolve the current authenticated user and their type.
     */
    private function resolveUser(): ?array
    {
        if (Auth::guard('siswa')->check()) {
            $user = Auth::guard('siswa')->user();

            return [
                'user' => $user,
                'type' => 'siswa',
                'guard' => 'siswa',
                'name' => $user->nama,
                'identifier' => $user->nis,
            ];
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            return [
                'user' => $user,
                'type' => 'admin',
                'guard' => 'web',
                'name' => $user->name ?? $user->username,
                'identifier' => $user->email ?? $user->username,
            ];
        }

        if (Auth::guard('ortu')->check()) {
            $user = Auth::guard('ortu')->user();

            return [
                'user' => $user,
                'type' => 'ortu',
                'guard' => 'ortu',
                'name' => $user->nama ?? $user->nama_wali ?? 'Orang Tua',
                'identifier' => $user->ortu_username ?? (string) $user->id,
            ];
        }

        return null;
    }

    /**
     * Generate registration options (challenge) for biometric setup.
     */
    public function registerOptions(Request $request)
    {
        $auth = $this->resolveUser();
        if (! $auth) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($response = $this->ensureServerVerificationSchemaResponse()) {
            return $response;
        }

        try {
            $webauthn = $this->makeWebAuthn($request);
            $excludeCredentials = WebAuthnCredential::where('user_id', $auth['user']->id)
                ->where('user_type', $auth['type'])
                ->pluck('credential_id')
                ->filter()
                ->map(function (string $credentialId) use ($auth) {
                    try {
                        return ByteBuffer::fromBase64Url($credentialId);
                    } catch (\Throwable $exception) {
                        \Log::warning('Skipping invalid stored WebAuthn credential id during registerOptions.', [
                            'user_id' => $auth['user']->id,
                            'user_type' => $auth['type'],
                            'credential_id_prefix' => substr($credentialId, 0, 24),
                            'message' => $exception->getMessage(),
                        ]);

                        return null;
                    }
                })
                ->filter()
                ->all();

            $options = $webauthn->getCreateArgs(
                userId: $this->makeUserHandleBinary($auth),
                userName: $auth['identifier'],
                userDisplayName: $auth['name'],
                timeout: 60,
                requireResidentKey: 'required',
                requireUserVerification: 'required',
                crossPlatformAttachment: null,
                excludeCredentialIds: $excludeCredentials,
            );

            session([
                'webauthn_challenge' => $this->encodeChallenge($webauthn->getChallenge()),
            ]);

            return response()->json($options);
        } catch (\Throwable $e) {
            \Log::error('WebAuthn registerOptions error: ' . $e->getMessage(), [
                'user_id' => $auth['user']->id,
                'user_type' => $auth['type'],
                'host' => $request->getHost(),
            ]);

            return response()->json([
                'error' => 'Gagal menyiapkan registrasi biometrik.',
            ], 500);
        }
    }

    /**
     * Store the registered credential after verifying WebAuthn attestation.
     */
    public function register(Request $request)
    {
        $auth = $this->resolveUser();
        if (! $auth) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($response = $this->ensureServerVerificationSchemaResponse()) {
            return $response;
        }

        $request->validate([
            'credential_id' => 'required|string',
            'response.clientDataJSON' => 'required|string',
            'response.attestationObject' => 'required|string',
            'response.transports' => 'nullable|array',
            'response.transports.*' => 'string',
            'device_name' => 'nullable|string|max:100',
        ]);

        try {
            $challenge = $this->challengeFromSession('webauthn_challenge');
            $webauthn = $this->makeWebAuthn($request);

            $registration = $webauthn->processCreate(
                clientDataJSON: $this->decodeBase64Url($request->input('response.clientDataJSON')),
                attestationObject: $this->decodeBase64Url($request->input('response.attestationObject')),
                challenge: $challenge,
                requireUserVerification: true,
                requireUserPresent: true,
                failIfRootMismatch: false,
                requireCtsProfileMatch: true,
            );

            $credentialId = $this->normalizeCredentialIdForStorage($registration->credentialId ?? null);

            $existingCredential = WebAuthnCredential::where('credential_id', $credentialId)->first();
            if ($existingCredential && ! (
                (int) $existingCredential->user_id === (int) $auth['user']->id
                && $existingCredential->user_type === $auth['type']
            )) {
                return response()->json([
                    'error' => 'Perangkat ini sudah terdaftar pada akun lain.',
                ], 409);
            }

            $credential = WebAuthnCredential::updateOrCreate(
                [
                    'credential_id' => $credentialId,
                    'user_id' => $auth['user']->id,
                    'user_type' => $auth['type'],
                ],
                [
                    'credential_public_key' => $registration->credentialPublicKey,
                    'signature_counter' => $registration->signatureCounter,
                    'attestation_format' => $registration->attestationFormat,
                    'aaguid' => $this->normalizeAaguidForStorage($registration->AAGUID ?? null),
                    'transports' => $request->input('response.transports', []),
                    'user_handle' => $this->makeUserHandleEncoded($auth),
                    'user_verified' => $registration->userVerified,
                    'backup_eligible' => $registration->isBackupEligible,
                    'backed_up' => $registration->isBackedUp,
                    'device_name' => $request->input('device_name') ?: $this->detectDeviceName($request),
                ]
            );

            AuthLogger::logBiometricEvent(
                action: 'register_success',
                userId: $auth['user']->id,
                username: $auth['identifier'],
                ipAddress: $request->ip(),
                additionalData: [
                    'user_type' => $auth['type'],
                    'device_name' => $credential->device_name,
                    'attestation_format' => $credential->attestation_format,
                ]
            );

            session()->forget('webauthn_challenge');

            return response()->json([
                'success' => true,
                'message' => 'Biometrik berhasil didaftarkan!',
                'credential' => $credential,
            ]);
        } catch (WebAuthnException $e) {
            AuthLogger::logBiometricEvent(
                action: 'register_failed',
                userId: $auth['user']->id,
                username: $auth['identifier'],
                ipAddress: $request->ip(),
                additionalData: [
                    'message' => $e->getMessage(),
                    'user_type' => $auth['type'],
                    'user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'success' => false,
                'error' => $this->translateWebAuthnError($e, 'Registrasi biometrik gagal.'),
            ], 422);
        } catch (\Throwable $e) {
            AuthLogger::logBiometricEvent(
                action: 'register_failed',
                userId: $auth['user']->id,
                username: $auth['identifier'],
                ipAddress: $request->ip(),
                additionalData: [
                    'message' => $e->getMessage(),
                    'user_type' => $auth['type'],
                    'user_agent' => $request->userAgent(),
                ]
            );

            \Log::error('WebAuthn register error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan server. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Generate login options for biometric authentication (no auth needed).
     */
    public function loginOptions(Request $request)
    {
        if ($response = $this->ensureServerVerificationSchemaResponse(loginFlow: true)) {
            return $response;
        }

        try {
            $webauthn = $this->makeWebAuthn($request);
            $options = $webauthn->getGetArgs(
                credentialIds: [],
                timeout: 60,
                allowUsb: true,
                allowNfc: true,
                allowBle: true,
                allowHybrid: true,
                allowInternal: true,
                requireUserVerification: 'required',
            );

            session([
                'webauthn_login_challenge' => $this->encodeChallenge($webauthn->getChallenge()),
            ]);

            return response()->json($options);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Gagal menyiapkan login biometrik.',
            ], 500);
        }
    }

    /**
     * Verify biometric login and authenticate user.
     */
    public function login(Request $request)
    {
        if ($response = $this->ensureServerVerificationSchemaResponse(loginFlow: true)) {
            return $response;
        }

        $request->validate([
            'credential_id' => 'required|string',
            'response.clientDataJSON' => 'required|string',
            'response.authenticatorData' => 'required|string',
            'response.signature' => 'required|string',
            'response.userHandle' => 'nullable|string',
        ]);

        $credential = WebAuthnCredential::where('credential_id', $request->credential_id)->first();

        if (! $credential) {
            AuthLogger::logBiometricEvent(
                action: 'login_failed',
                ipAddress: $request->ip(),
                additionalData: [
                    'reason' => 'credential_not_found',
                    'credential_id_prefix' => substr($request->credential_id, 0, 16),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Biometrik tidak dikenali. Silakan login dengan username dan password.',
            ], 401);
        }

        if (blank($credential->credential_public_key)) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat biometrik ini memakai format lama. Silakan login biasa lalu daftarkan ulang biometrik.',
            ], 409);
        }

        $user = $credential->getUser();
        $guard = $credential->getGuardName();

        if (! $user) {
            AuthLogger::logBiometricEvent(
                action: 'login_failed',
                ipAddress: $request->ip(),
                additionalData: [
                    'reason' => 'user_not_found',
                    'user_type' => $credential->user_type,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
            ], 401);
        }

        if (in_array($credential->user_type, ['siswa', 'ortu'], true) && method_exists($user, 'canLogin') && ! $user->canLogin()) {
            AuthLogger::logBiometricEvent(
                action: 'login_failed',
                userId: $user->id,
                username: $this->resolveIdentifierForLog($user, $credential->user_type),
                ipAddress: $request->ip(),
                additionalData: [
                    'reason' => 'inactive_user',
                    'user_type' => $credential->user_type,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif.',
            ], 401);
        }

        $userHandle = $request->input('response.userHandle');
        if ($userHandle && $credential->user_handle && ! hash_equals($credential->user_handle, $userHandle)) {
            return response()->json([
                'success' => false,
                'message' => 'Biometrik tidak cocok dengan akun ini.',
            ], 401);
        }

        try {
            $challenge = $this->challengeFromSession('webauthn_login_challenge');
            $webauthn = $this->makeWebAuthn($request);
            $webauthn->processGet(
                clientDataJSON: $this->decodeBase64Url($request->input('response.clientDataJSON')),
                authenticatorData: $this->decodeBase64Url($request->input('response.authenticatorData')),
                signature: $this->decodeBase64Url($request->input('response.signature')),
                credentialPublicKey: $credential->credential_public_key,
                challenge: $challenge,
                prevSignatureCnt: $credential->signature_counter,
                requireUserVerification: true,
                requireUserPresent: true,
            );

            Auth::guard($guard)->login($user, true);
            $this->recordUserLogin($user, $credential->user_type, $request);

            $credential->update([
                'last_used_at' => now(),
                'signature_counter' => $webauthn->getSignatureCounter() ?? $credential->signature_counter,
            ]);

            $request->session()->regenerate();
            session()->forget('webauthn_login_challenge');

            AuthLogger::logBiometricEvent(
                action: 'login_success',
                userId: $user->id,
                username: $this->resolveIdentifierForLog($user, $credential->user_type),
                ipAddress: $request->ip(),
                additionalData: [
                    'user_type' => $credential->user_type,
                    'guard' => $guard,
                    'device_name' => $credential->device_name,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil!',
                'redirect' => route($credential->getDashboardRoute()),
                'user_type' => $credential->user_type,
            ]);
        } catch (WebAuthnException $e) {
            AuthLogger::logBiometricEvent(
                action: 'login_failed',
                userId: $user->id,
                username: $this->resolveIdentifierForLog($user, $credential->user_type),
                ipAddress: $request->ip(),
                additionalData: [
                    'reason' => 'verification_failed',
                    'message' => $e->getMessage(),
                    'user_type' => $credential->user_type,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => $this->translateWebAuthnError($e, 'Verifikasi biometrik gagal.'),
            ], 422);
        } catch (\Throwable $e) {
            AuthLogger::logBiometricEvent(
                action: 'login_failed',
                userId: $user->id,
                username: $this->resolveIdentifierForLog($user, $credential->user_type),
                ipAddress: $request->ip(),
                additionalData: [
                    'reason' => 'server_error',
                    'message' => $e->getMessage(),
                    'user_type' => $credential->user_type,
                ]
            );

            \Log::error('WebAuthn login error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat login biometrik.',
            ], 500);
        }
    }

    /**
     * Check if the active user has credentials.
     */
    public function hasCredentials()
    {
        $auth = $this->resolveUser();
        if (! $auth) {
            return response()->json(['has_credentials' => false]);
        }

        if (! WebAuthnCredential::supportsCredentialPublicKey()) {
            return response()->json(['has_credentials' => false]);
        }

        $hasCredentials = WebAuthnCredential::where('user_id', $auth['user']->id)
            ->where('user_type', $auth['type'])
            ->whereNotNull('credential_public_key')
            ->exists();

        return response()->json(['has_credentials' => $hasCredentials]);
    }

    /**
     * Get biometric status for current user.
     */
    public function status()
    {
        $auth = $this->resolveUser();
        if (! $auth) {
            return response()->json(['has_biometric' => false]);
        }

        $credentials = $this->loadCredentialsForUser($auth);
        $validCredentials = $credentials->filter(fn ($credential) => ! blank($credential->credential_public_key));

        return response()->json([
            'has_biometric' => $validCredentials->count() > 0,
            'legacy_credential_count' => $credentials->count() - $validCredentials->count(),
            'credentials' => $credentials,
        ]);
    }

    /**
     * Delete a biometric credential.
     */
    public function destroy(Request $request, $id)
    {
        $auth = $this->resolveUser();
        if (! $auth) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $credential = WebAuthnCredential::where('id', $id)
            ->where('user_id', $auth['user']->id)
            ->where('user_type', $auth['type'])
            ->firstOrFail();

        $deviceName = $credential->device_name;
        $credential->delete();

        AuthLogger::logBiometricEvent(
            action: 'delete_success',
            userId: $auth['user']->id,
            username: $auth['identifier'],
            ipAddress: $request->ip(),
            additionalData: [
                'user_type' => $auth['type'],
                'device_name' => $deviceName,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat biometrik berhasil dihapus.',
        ]);
    }

    /**
     * Dismiss biometric prompt for this session.
     */
    public function dismissPrompt(Request $request)
    {
        session(['biometric_prompt_dismissed' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Show biometric settings page.
     */
    public function settingsPage(Request $request)
    {
        $auth = $this->resolveUser();
        if (! $auth) {
            return redirect()->route('login');
        }

        $credentials = $this->loadCredentialsForUser($auth);
        $legacyCredentialCount = $credentials->filter(fn (WebAuthnCredential $credential) => blank($credential->credential_public_key))->count();
        $validCredentialCount = $credentials->count() - $legacyCredentialCount;
        $webauthnEnvironment = $this->resolveEnvironmentStatus($request);

        if ($auth['type'] === 'siswa') {
            return view('siswa.biometrik', compact('credentials', 'legacyCredentialCount', 'validCredentialCount', 'webauthnEnvironment'));
        }

        if ($auth['type'] === 'ortu') {
            return view('ortu.biometrik', compact('credentials', 'legacyCredentialCount', 'validCredentialCount', 'webauthnEnvironment'));
        }

        if (Auth::user()?->isGuru()) {
            return view('guru.biometrik', compact('credentials', 'legacyCredentialCount', 'validCredentialCount', 'webauthnEnvironment'));
        }

        return view('admin.biometrik', compact('credentials', 'legacyCredentialCount', 'validCredentialCount', 'webauthnEnvironment'));
    }

    private function makeWebAuthn(Request $request): WebAuthn
    {
        return new WebAuthn(
            rpName: config('app.name', 'PKG Presensi'),
            rpId: $request->getHost(),
            allowedFormats: ['none'],
            useBase64UrlEncoding: true,
        );
    }

    private function challengeFromSession(string $sessionKey): ByteBuffer
    {
        $challenge = session($sessionKey);
        if (! is_string($challenge) || $challenge === '') {
            throw new WebAuthnException('challenge not found', WebAuthnException::INVALID_CHALLENGE);
        }

        return ByteBuffer::fromBase64Url($challenge);
    }

    private function encodeChallenge(ByteBuffer $challenge): string
    {
        return $challenge->jsonSerialize();
    }

    private function normalizeCredentialIdForStorage(mixed $credentialId): string
    {
        if (is_object($credentialId) && method_exists($credentialId, 'jsonSerialize')) {
            $serialized = $credentialId->jsonSerialize();
            if (is_string($serialized) && $serialized !== '') {
                return $serialized;
            }
        }

        if (is_string($credentialId) && $credentialId !== '') {
            if (preg_match('/^[A-Za-z0-9\-_]+$/', $credentialId) === 1) {
                return $credentialId;
            }

            return rtrim(strtr(base64_encode($credentialId), '+/', '-_'), '=');
        }

        throw new \RuntimeException('Invalid WebAuthn credential id payload.');
    }

    private function normalizeAaguidForStorage(mixed $aaguid): ?string
    {
        if ($aaguid === null || $aaguid === '') {
            return null;
        }

        if (is_object($aaguid) && method_exists($aaguid, 'getHex')) {
            $hex = $aaguid->getHex();

            return is_string($hex) && $hex !== '' ? strtolower($hex) : null;
        }

        if (is_string($aaguid)) {
            if (preg_match('/^[a-fA-F0-9-]+$/', $aaguid) === 1) {
                return strtolower($aaguid);
            }

            return strtolower(bin2hex($aaguid));
        }

        return null;
    }

    private function decodeBase64Url(string $payload): string
    {
        return ByteBuffer::fromBase64Url($payload)->getBinaryString();
    }

    private function makeUserHandleBinary(array $auth): string
    {
        return $auth['type'] . ':' . $auth['user']->id;
    }

    private function makeUserHandleEncoded(array $auth): string
    {
        return rtrim(strtr(base64_encode($this->makeUserHandleBinary($auth)), '+/', '-_'), '=');
    }

    private function detectDeviceName(Request $request): string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Android')) {
            return 'Android';
        }
        if (str_contains($ua, 'iPhone')) {
            return 'iPhone';
        }
        if (str_contains($ua, 'iPad')) {
            return 'iPad';
        }
        if (str_contains($ua, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($ua, 'Mac')) {
            return 'Mac';
        }

        return 'Perangkat';
    }

    private function resolveEnvironmentStatus(Request $request): array
    {
        $currentHost = $request->getHost();
        $appUrl = (string) config('app.url', '');
        $appUrlHost = parse_url($appUrl, PHP_URL_HOST);
        $currentScheme = $request->isSecure() ? 'https' : 'http';

        $isLoopbackHost = in_array($currentHost, ['localhost', '127.0.0.1', '::1'], true)
            || Str::endsWith($currentHost, ['.test', '.localhost']);

        $warnings = [];

        if (! $isLoopbackHost && ! $request->isSecure()) {
            $warnings[] = 'WebAuthn production sebaiknya diakses lewat HTTPS. Host aktif ini masih terbaca sebagai HTTP.';
        }

        if ($appUrlHost && ! hash_equals(strtolower($appUrlHost), strtolower($currentHost))) {
            $warnings[] = "Host aktif {$currentHost} berbeda dari host APP_URL {$appUrlHost}. Challenge biometrik bisa gagal jika domain login tidak konsisten.";
        }

        return [
            'current_host' => $currentHost,
            'current_origin' => "{$currentScheme}://{$currentHost}",
            'app_url' => $appUrl,
            'app_url_host' => $appUrlHost,
            'is_loopback_host' => $isLoopbackHost,
            'is_secure' => $request->isSecure(),
            'warnings' => $warnings,
        ];
    }

    private function recordUserLogin($user, string $userType, Request $request): void
    {
        if ($userType === 'admin' && method_exists($user, 'recordLogin')) {
            $user->recordLogin($request->ip(), $request->userAgent());
            return;
        }

        if ($userType === 'ortu') {
            $user->update(['ortu_last_login_at' => now()]);
            return;
        }

        if (method_exists($user, 'update')) {
            $user->update(['last_login_at' => now()]);
        }
    }

    private function resolveIdentifierForLog($user, string $userType): ?string
    {
        return match ($userType) {
            'siswa' => $user->nis ?? null,
            'ortu' => $user->ortu_username ?? $user->nis ?? null,
            default => $user->username ?? $user->email ?? null,
        };
    }

    private function translateWebAuthnError(WebAuthnException $exception, string $fallback): string
    {
        return match ($exception->getMessage()) {
            'challenge not found',
            'invalid challenge' => 'Sesi biometrik sudah kedaluwarsa. Coba lagi.',
            'invalid origin' => 'Permintaan biometrik berasal dari origin yang tidak valid.',
            'invalid signature' => 'Tanda tangan biometrik tidak valid.',
            'signature counter not valid' => 'Perangkat biometrik terdeteksi tidak aman. Silakan daftarkan ulang.',
            'user not verified during authentication',
            'user not verificated during authentication' => 'Perangkat tidak mengonfirmasi verifikasi pengguna.',
            default => $fallback,
        };
    }

    private function ensureServerVerificationSchemaResponse(bool $loginFlow = false)
    {
        if (WebAuthnCredential::supportsServerVerification()) {
            return null;
        }

        $message = $loginFlow
            ? 'Login biometrik belum siap di server ini. Silakan login dengan username dan password terlebih dahulu.'
            : 'Fitur biometrik belum siap di server ini karena migrasi database terbaru belum diterapkan.';

        return response()->json([
            'success' => false,
            'error' => $message,
            'message' => $message,
        ], 503);
    }

    private function loadCredentialsForUser(array $auth)
    {
        $columns = ['id', 'credential_id', 'device_name', 'last_used_at', 'created_at'];

        if (WebAuthnCredential::supportsCredentialPublicKey()) {
            $columns[] = 'credential_public_key';
        }

        $credentials = WebAuthnCredential::where('user_id', $auth['user']->id)
            ->where('user_type', $auth['type'])
            ->orderBy('created_at', 'desc')
            ->get($columns);

        if (! WebAuthnCredential::supportsCredentialPublicKey()) {
            $credentials->each(function (WebAuthnCredential $credential) {
                $credential->setAttribute('credential_public_key', null);
            });
        }

        return $credentials;
    }
}
