<?php

namespace App\Http\Requests;

use App\Models\Siswa;
use App\Support\TargetGrade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGenerusRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_mode' => ['required', Rule::in(['new', 'existing'])],
            'selected_student_token' => ['nullable', 'required_if:registration_mode,existing', 'string', 'max:2000'],
            'parent_name' => ['required', 'string', 'max:120'],
            'parent_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'student_name' => ['required', 'string', 'max:120'],
            'student_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{8,30}$/'],
            'kelompok' => ['required', Rule::in(array_keys(Siswa::kelompokOptions()))],
            'birth_place' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'school_grade' => ['required', Rule::in(TargetGrade::values())],
            'parent_signature' => ['required', 'string', 'max:1400000'],
            'student_signature' => ['required', 'string', 'max:1400000'],
            'statement_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_phone.regex' => 'Nomor WhatsApp orang tua tidak valid.',
            'student_phone.regex' => 'Nomor WhatsApp Generus tidak valid.',
            'parent_signature.required' => 'Tanda tangan orang tua wajib diisi.',
            'student_signature.required' => 'Tanda tangan Generus wajib diisi.',
            'statement_accepted.accepted' => 'Pernyataan harus disetujui sebelum mendaftar.',
            'selected_student_token.required_if' => 'Pilih dan verifikasi akun Generus terlebih dahulu.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'registration_mode' => $this->input('registration_mode', 'new'),
        ]);
    }
}
