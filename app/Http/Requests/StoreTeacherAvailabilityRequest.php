<?php

namespace App\Http\Requests;

use App\Models\TeacherProfile;
use App\Support\ParticipantProfileOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unavailable = $this->input('participation_role') === TeacherProfile::ROLE_UNAVAILABLE;

        return [
            'name' => ['required', 'string', 'min:3', 'max:160', 'regex:/^[\pL\pN\s.\x27,-]+$/u'],
            'kelompok' => ['required', Rule::in(array_keys(ParticipantProfileOptions::groups()))],
            'whatsapp' => ['required', 'string', 'max:24', 'regex:/^[0-9+()\-\s]{8,24}$/'],
            'participation_role' => ['required', Rule::in([
                TeacherProfile::ROLE_BOTH,
                TeacherProfile::ROLE_MAIN,
                TeacherProfile::ROLE_BACKUP,
                TeacherProfile::ROLE_AS_NEEDED,
                TeacherProfile::ROLE_UNAVAILABLE,
            ])],
            'rombels' => [$unavailable ? 'nullable' : 'required', 'array'],
            'rombels.*' => [Rule::in(array_keys(TeacherProfile::ROMBELS))],
            'available_nights' => [$unavailable ? 'nullable' : 'required', 'array'],
            'available_nights.*' => [Rule::in(array_keys(TeacherProfile::NIGHTS))],
            'night_priorities' => [$unavailable ? 'nullable' : 'required', 'array'],
            'night_priorities.*' => ['nullable', 'integer', 'min:1', 'max:3'],
            'monthly_limit' => [$unavailable ? 'nullable' : 'required', Rule::in(['1', '2', '3', '4_plus'])],
            'competencies' => [$unavailable ? 'nullable' : 'required', 'array'],
            'competencies.*' => [Rule::in([
                'quran', 'hadith', 'memorization', 'practice', 'class_support', 'all_materials',
            ])],
            'material_readiness' => [$unavailable ? 'nullable' : 'required', Rule::in(['ready', 'needs_support'])],
            'backup_contact_preference' => [$unavailable ? 'nullable' : 'required', Rule::in([
                'ready', 'one_day_notice', 'unavailable',
            ])],
            'constraints' => [$unavailable ? 'required' : 'nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $priorities = collect($this->input('night_priorities', []))
            ->filter(fn ($value, $night) => in_array($night, $this->input('available_nights', []), true) && filled($value))
            ->map(fn ($value) => (int) $value)
            ->all();

        $this->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name'))),
            'whatsapp' => trim((string) $this->input('whatsapp')),
            'night_priorities' => $priorities,
        ]);
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($this->input('participation_role') === TeacherProfile::ROLE_UNAVAILABLE) {
                    return;
                }

                $nights = array_values(array_unique($this->input('available_nights', [])));
                $priorities = $this->input('night_priorities', []);

                if (count($priorities) !== count($nights)
                    || count(array_unique(array_values($priorities))) !== count($priorities)) {
                    $validator->errors()->add(
                        'night_priorities',
                        'Setiap malam yang dipilih harus memiliki urutan prioritas yang berbeda.'
                    );
                }

                $expected = range(1, count($nights));
                $actual = array_values($priorities);
                sort($actual);

                if ($actual !== $expected) {
                    $validator->errors()->add(
                        'night_priorities',
                        'Urutan malam harus berurutan mulai dari pilihan pertama.'
                    );
                }
            },
        ];
    }
}
