<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuranReadingScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('quran-reading.max_upload_kilobytes', 8192);
        $maxDimension = (int) config('quran-reading.max_image_dimension', 8000);

        return [
            'sheet_payload' => ['required', 'string', 'max:500', 'regex:/^(?:PKGQURAN:[0-9a-f-]{36}:[A-Za-z0-9]+|PKGQ:[0-9A-F]{32}:[0-9A-F]{32})$/i'],
            'scan_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb, 'dimensions:max_width='.$maxDimension.',max_height='.$maxDimension],
            'processed_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb, 'dimensions:max_width='.$maxDimension.',max_height='.$maxDimension],
            'ocr_suggestion' => ['nullable', 'json', 'max:60000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sheet_payload.regex' => 'QR bukan lembar Tracer Bacaan Al-Qur’an PKG.',
            'scan_image.required' => 'Ambil foto atau pilih foto lembar terlebih dahulu.',
            'scan_image.max' => 'Ukuran foto maksimal 8 MB.',
            'scan_image.dimensions' => 'Dimensi foto terlalu besar. Gunakan foto maksimal 8000 × 8000 piksel.',
        ];
    }
}
