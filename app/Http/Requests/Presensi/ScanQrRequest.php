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
