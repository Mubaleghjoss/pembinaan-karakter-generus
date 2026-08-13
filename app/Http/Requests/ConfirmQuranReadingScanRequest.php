<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmQuranReadingScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:12'],
            'ocr_suggestion' => ['nullable', 'json', 'max:60000'],
            'rows.*.row_number' => ['required', 'integer', 'between:1,12', 'distinct'],
            'rows.*.reading_date' => ['required', 'date', 'before_or_equal:today'],
            'rows.*.page_start' => ['required', 'integer', 'between:1,1000'],
            'rows.*.page_end' => ['required', 'integer', 'between:1,1000'],
            'rows.*.surah_start' => ['required', 'integer', 'between:1,114'],
            'rows.*.ayah_start' => ['required', 'integer', 'between:1,286'],
            'rows.*.surah_end' => ['required', 'integer', 'between:1,114'],
            'rows.*.ayah_end' => ['required', 'integer', 'between:1,286'],
            'rows.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
