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
        if ($this->route('scan')?->sheet?->sheet_type === 'surah_map') {
            return [
                'completed_surahs' => ['nullable', 'array', 'max:114'],
                'completed_surahs.*' => ['integer', 'between:1,114', 'distinct'],
                'ambiguous_surahs' => ['nullable', 'array', 'max:114'],
                'ambiguous_surahs.*' => ['integer', 'between:1,114', 'distinct'],
                'active_surah' => ['nullable', 'integer', 'between:1,114', 'required_with:active_ayah'],
                'active_ayah' => ['nullable', 'integer', 'between:1,286', 'required_with:active_surah'],
                'marked_on' => ['nullable', 'date', 'before_or_equal:today'],
                'ocr_suggestion' => ['nullable', 'json', 'max:60000'],
            ];
        }

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
