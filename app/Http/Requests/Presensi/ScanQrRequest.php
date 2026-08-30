<?php

namespace App\Http\Requests\Presensi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request untuk validasi scan QR Code.
 *
 * Memvalidasi data yang dikirim saat siswa melakukan scan QR Code
 * untuk mencatat kehadiran.
 */
class ScanQrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi isi QR menjadi `qr_data` sebelum validasi.
     *
     * PresensiController membaca `$request->validated('qr_data')['student_id']`,
     * tetapi `qr_data` tidak pernah ada di rules() sehingga `validated()`
     * mengembalikan null dan endpoint selalu HTTP 500
     * ("Trying to access array offset on value of type null").
     *
     * Isi QR yang dihasilkan QrTokenService::buildPayload() berformat
     * `PKG|VERSION|STUDENT_ID|TOKEN|HASH`; klien lama juga boleh mengirim JSON
     * `{"student_id": ..., "token": ...}` atau field `student_id` datar.
     * Ketiganya diurai di sini menjadi bentuk tunggal `qr_data`.
     */
    protected function prepareForValidation(): void
    {
        $raw = $this->input('token');

        if (! is_string($raw)) {
            return;
        }

        $parsed = $this->parseQrToken(trim($raw));

        if ($parsed === null) {
            return;
        }

        $this->merge([
            'student_id' => $parsed['student_id'],
            'qr_data' => $parsed,
        ]);
    }

    /**
     * @return array{student_id: int, token: string}|null
     */
    private function parseQrToken(string $raw): ?array
    {
        // Bentuk 1: PKG|VERSION|STUDENT_ID|TOKEN|HASH
        if (str_contains($raw, '|')) {
            $parts = explode('|', $raw);

            if (count($parts) >= 4 && ctype_digit(trim($parts[2]))) {
                return [
                    'student_id' => (int) trim($parts[2]),
                    'token' => trim($parts[3]),
                ];
            }

            return null;
        }

        // Bentuk 2: JSON {"student_id": ..., "token": ...}
        if (str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)
                && isset($decoded['student_id'], $decoded['token'])
                && is_string($decoded['token'])
            ) {
                return [
                    'student_id' => (int) $decoded['student_id'],
                    'token' => $decoded['token'],
                ];
            }

            return null;
        }

        // Bentuk 3: token polos + student_id dikirim terpisah oleh klien.
        $studentId = $this->input('student_id');

        if (is_numeric($studentId)) {
            return ['student_id' => (int) $studentId, 'token' => $raw];
        }

        return null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Token bisa berupa:
            // 1. PKG format: PKG|VERSION|STUDENT_ID|TOKEN|HASH
            // 2. JSON format: {"student_id": ..., "token": ...}
            'token' => ['required', 'string', 'min:10'],
            'location' => ['nullable', 'string', 'max:255'],
            // Diisi oleh prepareForValidation() dari `token`; dibutuhkan oleh
            // PresensiController::scanQr().
            'qr_data' => ['required', 'array'],
            'qr_data.student_id' => ['required', 'integer', 'min:1'],
            'qr_data.token' => ['required', 'string', 'min:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Token QR wajib diisi.',
            'token.string' => 'Token QR harus berupa string.',
            'token.min' => 'Token QR tidak valid.',
            'qr_data.required' => 'Format QR tidak dikenali.',
            'qr_data.student_id.required' => 'QR tidak memuat ID siswa.',
            'qr_data.token.required' => 'QR tidak memuat token.',
            'qr_data.token.min' => 'Token QR tidak valid.',
            'location.string' => 'Lokasi harus berupa string.',
            'location.max' => 'Lokasi maksimal 255 karakter.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'ID Siswa',
            'token' => 'Token QR',
            'location' => 'Lokasi',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
