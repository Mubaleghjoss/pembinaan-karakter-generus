<?php

namespace App\Http\Controllers;

use App\Models\TeacherMaterial;
use App\Models\TeacherProfile;
use App\Models\TeacherScheduleAssignment;
use App\Services\Contracts\PamongQrServiceInterface;
use App\Services\TeacherStatementDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherPortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $profile = $this->teacherProfile($request);
        $base = $this->assignmentQuery($profile);
        $upcomingAssignments = (clone $base)
            ->whereHas('session', fn ($query) => $query->whereDate('session_date', '>=', today()))
            ->orderBy(
                $this->sessionDateSubquery()
            )
            ->limit(5)
            ->get();
        $nextAssignment = $upcomingAssignments->first();
        $monthAssignments = (clone $base)
            ->whereHas('session', fn ($query) => $query
                ->whereYear('session_date', now()->year)
                ->whereMonth('session_date', now()->month))
            ->get();

        return view('guru.dashboard', [
            'profile' => $profile,
            'nextAssignment' => $nextAssignment,
            'upcomingAssignments' => $upcomingAssignments,
            'monthStats' => [
                'total' => $monthAssignments->count(),
                'main' => $monthAssignments->where('role', 'main')->count(),
                'backup' => $monthAssignments->where('role', 'backup')->count(),
            ],
        ]);
    }

    public function schedule(Request $request): View
    {
        $profile = $this->teacherProfile($request);
        $assignments = $this->assignmentQuery($profile)
            ->orderBy($this->sessionDateSubquery(), 'desc')
            ->paginate(20);

        return view('guru.schedule', compact('profile', 'assignments'));
    }

    public function scheduleShow(Request $request, TeacherScheduleAssignment $assignment): View
    {
        $profile = $this->teacherProfile($request);
        abort_unless($assignment->teacher_profile_id === $profile->id, 404);
        $assignment->loadMissing(['session.materials', 'session.period']);
        abort_unless($assignment->session?->period?->status === 'published', 404);

        return view('guru.schedule-show', compact('profile', 'assignment'));
    }

    public function materials(Request $request): View
    {
        $profile = $this->teacherProfile($request);
        $materials = TeacherMaterial::query()
            ->active()
            ->with(['sessions' => fn ($query) => $query
                ->whereHas('period', fn ($period) => $period->where('status', 'published'))
                ->whereHas('assignments', fn ($assignment) => $assignment->where('teacher_profile_id', $profile->id))])
            ->orderBy('title')
            ->get()
            ->filter(function (TeacherMaterial $material) use ($profile): bool {
                $targets = $material->rombels ?? [];

                return $material->sessions->isNotEmpty()
                    || $targets === []
                    || collect($targets)->intersect($profile->rombels ?? [])->isNotEmpty();
            })
            ->values();

        return view('guru.materials', compact('profile', 'materials'));
    }

    public function profile(Request $request): View
    {
        return view('guru.profile', [
            'profile' => $this->teacherProfile($request),
            'rombels' => TeacherProfile::ROMBELS,
            'nights' => TeacherProfile::NIGHTS,
            'participationRoles' => TeacherProfile::PARTICIPATION_ROLES,
            'competencies' => TeacherProfile::COMPETENCIES,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $profile = $this->teacherProfile($request);
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['required', 'string', 'max:24'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);
        $phone = $this->normalizeWhatsapp($validated['whatsapp']);
        if (TeacherProfile::query()->where('whatsapp_normalized', $phone)->whereKeyNot($profile->id)->exists()) {
            throw ValidationException::withMessages(['whatsapp' => 'Nomor WhatsApp sudah dipakai profil lain.']);
        }

        $oldAvatarPath = $user->avatar_path;
        $avatarPath = $oldAvatarPath;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        DB::transaction(function () use ($user, $profile, $validated, $phone, $avatarPath): void {
            $name = preg_replace('/\s+/', ' ', trim($validated['name']));
            $user->update([
                'name' => $name,
                'email' => trim($validated['email'] ?? '') ?: null,
                'phone' => $phone,
                'avatar_path' => $avatarPath,
            ]);
            $profile->update([
                'name' => $name,
                'whatsapp' => trim($validated['whatsapp']),
                'whatsapp_normalized' => $phone,
            ]);
        });

        if ($avatarPath !== $oldAvatarPath && filled($oldAvatarPath)) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return back()->with('success', 'Profil Guru berhasil diperbarui.');
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $profile = $this->teacherProfile($request);
        $validated = $request->validate([
            'participation_role' => ['required', Rule::in(array_keys(TeacherProfile::PARTICIPATION_ROLES))],
            'rombels' => ['nullable', 'array'],
            'rombels.*' => [Rule::in(array_keys(TeacherProfile::ROMBELS))],
            'available_nights' => ['nullable', 'array'],
            'available_nights.*' => [Rule::in(array_keys(TeacherProfile::NIGHTS))],
            'night_priorities' => ['nullable', 'array'],
            'night_priorities.*' => ['nullable', 'integer', 'min:1', 'max:3'],
            'monthly_limit' => ['nullable', 'integer', 'min:1', 'max:3'],
            'competencies' => ['nullable', 'array'],
            'competencies.*' => [Rule::in(array_keys(TeacherProfile::COMPETENCIES))],
            'material_readiness' => ['required', Rule::in(['ready', 'needs_support'])],
            'backup_contact_preference' => ['required', Rule::in(['ready', 'one_day_notice', 'unavailable'])],
            'constraints' => ['nullable', 'string', 'max:1000'],
        ]);
        $nights = array_values(array_unique($validated['available_nights'] ?? []));
        $priorities = collect($nights)
            ->sortBy(fn ($night) => (int) ($validated['night_priorities'][$night] ?? 99))
            ->values()
            ->mapWithKeys(fn ($night, $index) => [$night => $index + 1])
            ->all();

        $profile->update([
            ...$validated,
            'rombels' => array_values(array_unique($validated['rombels'] ?? [])),
            'available_nights' => $nights,
            'night_priorities' => $priorities,
            'competencies' => array_values(array_unique($validated['competencies'] ?? [])),
            'constraints' => trim($validated['constraints'] ?? '') ?: null,
        ]);

        return back()->with('success', 'Kesediaan diperbarui untuk penyusunan jadwal berikutnya. Jadwal yang sudah terbit tidak berubah.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_preference' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);
        $request->user()->update($validated);

        return back()->with('success', 'Tema aplikasi berhasil disimpan.');
    }

    public function password(Request $request): View
    {
        return view('guru.password', [
            'profile' => $this->teacherProfile($request),
            'firstLogin' => false,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        return redirect()->route('guru.profile')->with('success', 'Password berhasil diperbarui.');
    }

    public function initialPassword(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('guru.dashboard');
        }

        return view('guru.password', [
            'profile' => $this->teacherProfile($request),
            'firstLogin' => true,
        ]);
    }

    public function updateInitialPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        return redirect()->route('guru.dashboard')->with('success', 'Password baru tersimpan. Selamat datang di Portal Guru.');
    }

    public function statement(
        Request $request,
        TeacherStatementDocumentService $documentService
    ) {
        return $documentService->response($this->teacherProfile($request));
    }

    public function idCard(Request $request, PamongQrServiceInterface $qrService): View
    {
        $profile = $this->teacherProfile($request);
        $qrData = $qrService->getQrData($request->user());

        return view('guru.id-card', compact('profile', 'qrData'));
    }

    private function teacherProfile(Request $request): TeacherProfile
    {
        return $request->user()->teacherProfile()->firstOrFail();
    }

    private function assignmentQuery(TeacherProfile $profile)
    {
        return TeacherScheduleAssignment::query()
            ->where('teacher_profile_id', $profile->id)
            ->whereHas('session.period', fn ($query) => $query->where('status', 'published'))
            ->with(['session.period', 'session.materials']);
    }

    private function sessionDateSubquery()
    {
        return \App\Models\TeacherScheduleSession::query()
            ->select('session_date')
            ->whereColumn('teacher_schedule_sessions.id', 'teacher_schedule_assignments.session_id')
            ->limit(1);
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
            throw ValidationException::withMessages(['whatsapp' => 'Nomor WhatsApp tidak valid.']);
        }

        return $digits;
    }
}
