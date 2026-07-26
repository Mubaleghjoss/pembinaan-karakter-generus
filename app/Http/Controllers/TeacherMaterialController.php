<?php

namespace App\Http\Controllers;

use App\Models\TeacherMaterial;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherMaterialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $this->authorizeModule('view');
        $materials = TeacherMaterial::query()
            ->with('creator:id,name')
            ->withCount('sessions')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->paginate(20);

        return view('teacher-planning.materials', [
            'materials' => $materials,
            'rombels' => TeacherProfile::ROMBELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeModule('create');
        $validated = $this->validated($request);

        TeacherMaterial::query()->create([
            ...$validated,
            'title' => trim($validated['title']),
            'description' => trim($validated['description'] ?? '') ?: null,
            'google_drive_url' => trim($validated['google_drive_url']),
            'rombels' => array_values(array_unique($validated['rombels'] ?? [])),
            'is_active' => $request->boolean('is_active'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Materi Guru berhasil ditambahkan.');
    }

    public function update(Request $request, TeacherMaterial $teacherMaterial): RedirectResponse
    {
        $this->authorizeModule('edit');
        $validated = $this->validated($request);

        $teacherMaterial->update([
            ...$validated,
            'title' => trim($validated['title']),
            'description' => trim($validated['description'] ?? '') ?: null,
            'google_drive_url' => trim($validated['google_drive_url']),
            'rombels' => array_values(array_unique($validated['rombels'] ?? [])),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Materi Guru berhasil diperbarui.');
    }

    public function destroy(TeacherMaterial $teacherMaterial): RedirectResponse
    {
        $this->authorizeModule('delete');
        $teacherMaterial->delete();

        return back()->with('success', 'Materi Guru berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'google_drive_url' => ['required', 'url:https', 'max:1000'],
            'rombels' => ['nullable', 'array'],
            'rombels.*' => [Rule::in(array_keys(TeacherProfile::ROMBELS))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $host = strtolower((string) parse_url($validated['google_drive_url'], PHP_URL_HOST));
        if (! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
            throw ValidationException::withMessages([
                'google_drive_url' => 'Tautan harus memakai HTTPS dari drive.google.com atau docs.google.com.',
            ]);
        }

        return $validated;
    }

    private function authorizeModule(string $operation): void
    {
        $user = request()->user();
        abort_unless(
            $user && ($user->isAdmin()
                || ($user->hasPamongMenuAccess('teacher_scheduling')
                    && $user->hasPamongCrudPermission('teacher_scheduling', $operation))),
            403
        );
    }
}
