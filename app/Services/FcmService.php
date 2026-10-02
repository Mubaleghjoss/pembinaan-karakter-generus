<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * Send one notification through FCM HTTP v1.
     * Returns false for an invalid/unregistered token so the caller can revoke it.
     */
    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (! config('fcm.enabled')) {
            return false;
        }

        $projectId = (string) config('fcm.project_id');
        $credentialsPath = (string) config('fcm.credentials');
        if ($projectId === '' || $credentialsPath === '' || ! is_readable($credentialsPath)) {
            throw new RuntimeException('Konfigurasi FCM server belum lengkap.');
        }

        $caBundle = (string) config('fcm.ca_bundle');
        if ($caBundle !== '' && is_readable($caBundle)) {
            putenv('CURL_CA_BUNDLE='.$caBundle);
            putenv('SSL_CERT_FILE='.$caBundle);
        }

        $credentials = new ServiceAccountCredentials(
            [self::SCOPE],
            $credentialsPath,
        );
        $auth = $credentials->fetchAuthToken();
        $accessToken = $auth['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Token akses FCM tidak dapat dibuat.');
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => collect($data)->map(fn ($value) => (string) $value)->all(),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'pkg_aktivitas',
                        ],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        $status = $response->status();
        $errorCode = (string) data_get($response->json(), 'error.details.0.errorCode', '');
        if ($status === 404 || $errorCode === 'UNREGISTERED') {
            return false;
        }

        throw new RuntimeException("FCM mengembalikan HTTP {$status}.");
    }
}
