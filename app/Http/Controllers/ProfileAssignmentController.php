<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Support\TargetGrade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileAssignmentController extends Controller
{
    public function updateSiswa(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kelompok' => ['required', 'string', Rule::in(array_keys(Siswa::kelompokOptions()))],
            'target_grade_override' => ['required', 'string', Rule::in(TargetGrade::values())],
        ], [
            'kelompok.required' => 'Pilih kelompok terbaru.',
            'kelompok.in' => 'Kelompok yang dipilih tidak valid.',
            'target_grade_override.required' => 'Pilih kelas sekolah saat ini.',
            'target_grade_override.in' => 'Kelas sekolah yang dipilih tidak valid.',
        ]);

        $siswa = $request->user('siswa');
        abort_unless($siswa instanceof Siswa, 403);

        $siswa->update([
            'kelompok' => $validated['kelompok'],
            'target_grade_override' => $validated['target_grade_override'],
            'profile_assignment_confirmed_at' => now(),
        ]);

        return back()->with('success', 'Kelompok dan kelas sekolah berhasil diperbarui.');
    }

    public function updatePamong(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->usesPamongPermissionSystem(), 403);

        $validated = $request->validate([
            'kelompok' => ['required', 'string', Rule::in(array_keys(User::kelompokOptions()))],
        ], [
            'kelompok.required' => 'Pilih kelompok terbaru.',
            'kelompok.in' => 'Kelompok yang dipilih tidak valid.',
        ]);

        $user->update([
            'kelompok' => $validated['kelompok'],
            'profile_assignment_confirmed_at' => now(),
        ]);

        return back()->with('success', 'Kelompok pamong berhasil diperbarui.');
    }
}
