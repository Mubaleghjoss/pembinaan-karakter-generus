<?php

namespace App\Http\Requests\Presensi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Form Request untuk validasi pembuatan data presensi.
 *
 * Memvalidasi data yang dikirim saat admin/pamong mencatat
 * kehadiran siswa secara manual.
 */
class StorePresensiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya user yang terautentikasi yang bisa membuat presensi
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'siswa_id' => ['required', 'integer', 'exists:siswa,id'],
            'tanggal' => ['required', 'date', 'date_format:Y-m-d'],
            'status' => ['required', 'string', Rule::in(['hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'tidak_hadir'])],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i', 'after:jam_masuk'],
            'keterangan' => ['nullable', 'string', 'max:500'],
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
            'siswa_id.required' => 'Siswa wajib dipilih.',
            'siswa_id.integer' => 'ID siswa harus berupa angka.',
            'siswa_id.exists' => 'Siswa tidak ditemukan.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'tanggal.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'status.required' => 'Status kehadiran wajib dipilih.',
            'status.string' => 'Status harus berupa string.',
            'status.in' => 'Status kehadiran tidak valid. Pilih: hadir, terlambat, izin, sakit, atau alpha.',
            'jam_masuk.date_format' => 'Format jam masuk harus HH:MM.',
            'jam_keluar.date_format' => 'Format jam keluar harus HH:MM.',
            'jam_keluar.after' => 'Jam keluar harus setelah jam masuk.',
            'keterangan.string' => 'Keterangan harus berupa string.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
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
            'siswa_id' => 'Siswa',
            'tanggal' => 'Tanggal',
            'status' => 'Status Kehadiran',
            'jam_masuk' => 'Jam Masuk',
            'jam_keluar' => 'Jam Keluar',
            'keterangan' => 'Keterangan',
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('status') === 'tidak_hadir') {
            $this->merge(['status' => 'alpha']);
        }

        // Trim whitespace dari keterangan
        if ($this->has('keterangan') && is_string($this->keterangan)) {
            $this->merge([
                'keterangan' => trim($this->keterangan),
            ]);
        }
    }
}
