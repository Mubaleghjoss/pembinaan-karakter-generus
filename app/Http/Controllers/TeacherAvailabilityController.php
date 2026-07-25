<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherAvailabilityRequest;
use App\Models\TeacherAvailabilityInvite;
use App\Models\TeacherProfile;
use App\Models\ThemeSetting;
use App\Support\ParticipantProfileOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherAvailabilityController extends Controller
{
    private const INVITE_SESSION = 'teacher_availability.invite_id';
    private const UNLOCKED_AT_SESSION = 'teacher_availability.unlocked_at';
    private const ACCESS_TTL_SECONDS = 3600;

    public function index(Request $request)
    {
        $invite = $this->sessionInvite($request);
        $theme = ThemeSetting::current();

        if (! $invite) {
            return view('public.teacher-availability.access', compact('theme'));
        }

        return view('public.teacher-availability.form', [
            'theme' => $theme,
            'groups' => ParticipantProfileOptions::groups(),
            'rombels' => TeacherProfile::ROMBELS,
            'nights' => TeacherProfile::NIGHTS,
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'access_code' => ['required', 'string', 'min:6', 'max:32'],
        ]);
        $code = Str::upper(preg_replace('/\s+/', '', trim($validated['access_code'])));
        $invite = TeacherAvailabilityInvite::query()
            ->where('token_hash', hash('sha256', $code))
            ->first();

        if (! $invite?->isAvailable()) {
            throw ValidationException::withMessages([
                'access_code' => 'Kode akses tidak valid, kedaluwarsa, atau kuotanya sudah habis.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            self::INVITE_SESSION => $invite->id,
            self::UNLOCKED_AT_SESSION => now()->timestamp,
        ]);

        return redirect()->route('public.teacher-availability.index');
    }

    public function store(StoreTeacherAvailabilityRequest $request): RedirectResponse
    {
        $invite = $this->requireInvite($request);
        $data = $request->validated();
        $phone = $this->normalizeWhatsapp($data['whatsapp']);

        if (TeacherProfile::query()->where('whatsapp_normalized', $phone)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Data dengan nomor WhatsApp tersebut sudah tercatat. Hubungi admin apabila perlu memperbarui jawaban.',
            ]);
        }

        DB::transaction(function () use ($invite, $data, $phone): void {
            $lockedInvite = TeacherAvailabilityInvite::query()->lockForUpdate()->findOrFail($invite->id);

            if (! $lockedInvite->isAvailable()) {
                throw ValidationException::withMessages([
                    'access_code' => 'Kuota formulir telah habis. Silakan hubungi admin.',
                ]);
            }

            $unavailable = $data['participation_role'] === TeacherProfile::ROLE_UNAVAILABLE;

            TeacherProfile::create([
                'invite_id' => $lockedInvite->id,
                'name' => $data['name'],
                'public_name' => $this->defaultPublicName($data['name']),
                'kelompok' => $data['kelompok'],
                'whatsapp' => $data['whatsapp'],
                'whatsapp_normalized' => $phone,
                'participation_role' => $data['participation_role'],
                'rombels' => $unavailable ? [] : array_values(array_unique($data['rombels'] ?? [])),
                'available_nights' => $unavailable ? [] : array_values(array_unique($data['available_nights'] ?? [])),
                'night_priorities' => $unavailable ? [] : ($data['night_priorities'] ?? []),
                'monthly_limit' => $unavailable || $data['monthly_limit'] === '4_plus'
                    ? null
                    : (int) $data['monthly_limit'],
                'competencies' => $unavailable ? [] : array_values(array_unique($data['competencies'] ?? [])),
                'material_readiness' => $unavailable ? null : $data['material_readiness'],
                'backup_contact_preference' => $unavailable ? null : $data['backup_contact_preference'],
                'constraints' => $data['constraints'] ?? null,
                'consent_version' => 'v1',
                'consented_at' => now(),
                'submitted_at' => now(),
                'is_active' => true,
            ]);

            $lockedInvite->increment('used_count');
        });

        $request->session()->forget([self::INVITE_SESSION, self::UNLOCKED_AT_SESSION]);

        return redirect()->route('public.teacher-availability.success');
    }

    public function success()
    {
        return view('public.teacher-availability.success', ['theme' => ThemeSetting::current()]);
    }

    private function sessionInvite(Request $request): ?TeacherAvailabilityInvite
    {
        $inviteId = $request->session()->get(self::INVITE_SESSION);
        $unlockedAt = (int) $request->session()->get(self::UNLOCKED_AT_SESSION, 0);

        if (! $inviteId || ! $unlockedAt || now()->timestamp - $unlockedAt > self::ACCESS_TTL_SECONDS) {
            $request->session()->forget([self::INVITE_SESSION, self::UNLOCKED_AT_SESSION]);

            return null;
        }

        $invite = TeacherAvailabilityInvite::find($inviteId);

        if (! $invite?->isAvailable()) {
            $request->session()->forget([self::INVITE_SESSION, self::UNLOCKED_AT_SESSION]);

            return null;
        }

        return $invite;
    }

    private function requireInvite(Request $request): TeacherAvailabilityInvite
    {
        $invite = $this->sessionInvite($request);

        if (! $invite) {
            throw ValidationException::withMessages([
                'access_code' => 'Sesi formulir berakhir. Masukkan kembali kode akses.',
            ]);
        }

        return $invite;
    }

    private function normalizeWhatsapp(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        if (! preg_match('/^62[0-9]{8,13}$/', $digits)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        return $digits;
    }

    private function defaultPublicName(string $name): string
    {
        return Str::limit((preg_split('/\s+/', trim($name)) ?: ['Pengajar'])[0], 80, '');
    }
}
