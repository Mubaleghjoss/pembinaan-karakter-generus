<?php

namespace App\Http\Controllers;

use App\Models\TeacherScheduleAssignment;
use App\Models\ThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherConfirmationController extends Controller
{
    public function show(string $token)
    {
        $assignment = $this->assignment($token);
        $assignment->loadMissing(['teacher', 'session.period']);

        return view('public.teacher-confirmation.show', [
            'assignment' => $assignment,
            'token' => $token,
            'theme' => ThemeSetting::current(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $assignment = $this->assignment($token);
        abort_if($assignment->session->session_date->copy()->endOfDay()->isPast(), 422, 'Jadwal ini sudah berlalu.');
        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'declined'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $assignment->update([
            'confirmation_status' => $validated['status'],
            'confirmed_at' => now(),
            'confirmation_note' => trim($validated['note'] ?? '') ?: null,
        ]);

        return back()->with('success', $validated['status'] === 'confirmed'
            ? 'Terima kasih, kesediaan Anda sudah dikonfirmasi.'
            : 'Informasi berhalangan sudah diterima. Admin akan menyesuaikan jadwal.');
    }

    private function assignment(string $token): TeacherScheduleAssignment
    {
        abort_unless(strlen($token) === 64, 404);

        return TeacherScheduleAssignment::query()
            ->where('confirmation_token_hash', hash('sha256', $token))
            ->firstOrFail();
    }
}
