<?php

namespace App\Http\Requests\Siswa;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Support\TargetGrade;

/**
 * Form Request untuk validasi update data siswa.
 *
 * Memvalidasi data yang dikirim saat admin/pamong mengupdate
 * data siswa yang sudah terdaftar.
 */
class UpdateSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya user yang terautentikasi yang bisa update siswa
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Dapatkan ID siswa dari route parameter
        $siswaId = $this->route('siswa') ?? $this->route('id');

        return [
            'nis' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('siswa', 'nis')->ignore($siswaId),
            ],
            'nama' => ['sometimes', 'nullable', 'string', 'max:255'],
            'jenis_kelamin' => ['sometimes', 'nullable', 'string', Rule::in(['L', 'P', ''])],
            'tanggal_lahir' => ['sometimes', 'nullable', 'date'],
            'kelompok' => ['nullable', 'string', Rule::in(array_keys(\App\Models\Siswa::kelompokOptions()))],
            'kelas_id' => ['sometimes', 'nullable', 'integer', 'exists:kelas,id'],
            'school_grade' => ['sometimes', 'nullable', 'string', Rule::in(TargetGrade::values())],
            'target_grade_override' => ['nullable', 'string', Rule::in(TargetGrade::values())],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'nama_wali' => ['nullable', 'string', 'max:255'],
            'phone_wali' => ['nullable', 'string', 'max:20'],
            'email_wali' => ['nullable', 'email', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'inactive', 'graduated', 'transferred'])],
            'is_active' => ['sometimes', 'boolean'],
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
            'nis.string' => 'NIS harus berupa string.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nis.max' => 'NIS maksimal 20 karakter.',
            'nama.string' => 'Nama harus berupa string.',
            'nama.max' => 'Nama maksimal 255 karakter.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L (Laki-laki) atau P (Perempuan).',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'kelompok.in' => 'Kelompok harus salah satu dari daftar yang tersedia.',
            'kelas_id.integer' => 'ID kelas harus berupa angka.',
            'kelas_id.exists' => 'Kelas tidak ditemukan.',
            'school_grade.in' => 'Kelas sekolah tidak valid.',
            'target_grade_override.in' => 'Level kelas PKG tidak valid.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Format foto harus jpeg, png, atau jpg.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
            'nama_wali.string' => 'Nama wali harus berupa string.',
            'nama_wali.max' => 'Nama wali maksimal 255 karakter.',
            'phone_wali.string' => 'Nomor telepon wali harus berupa string.',
            'phone_wali.max' => 'Nomor telepon wali maksimal 20 karakter.',
            'email_wali.email' => 'Format email wali tidak valid.',
            'email_wali.max' => 'Email wali maksimal 255 karakter.',
            'status.in' => 'Status tidak valid. Pilih: active, inactive, graduated, atau transferred.',
            'is_active.boolean' => 'Status aktif harus berupa boolean.',
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
            'nis' => 'NIS',
            'nama' => 'Nama Siswa',
            'jenis_kelamin' => 'Jenis Kelamin',
            'tanggal_lahir' => 'Tanggal Lahir',
            'kelompok' => 'Kelompok',
            'kelas_id' => 'Kelas Arsip',
            'school_grade' => 'Kelas Sekolah',
            'target_grade_override' => 'Override Level PKG',
            'foto' => 'Foto',
            'nama_wali' => 'Nama Wali',
            'phone_wali' => 'Telepon Wali',
            'email_wali' => 'Email Wali',
            'status' => 'Status',
            'is_active' => 'Status Aktif',
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
        // Trim whitespace dari string fields
        $fieldsToTrim = ['nis', 'nama', 'kelompok', 'school_grade', 'target_grade_override', 'nama_wali', 'phone_wali', 'email_wali'];

        foreach ($fieldsToTrim as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $this->merge([
                    $field => trim($this->$field),
                ]);
            }
        }

        // Uppercase jenis_kelamin
        if ($this->has('jenis_kelamin') && is_string($this->jenis_kelamin)) {
            $this->merge([
                'jenis_kelamin' => strtoupper($this->jenis_kelamin),
            ]);
        }
    }
}
