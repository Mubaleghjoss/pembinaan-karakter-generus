<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Support\TargetGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $selectedPamong = $request->filled('pamong_id') ? $request->integer('pamong_id') : null;
        $schoolGrade = $request->input('school_grade');
        $kelompok = Siswa::normalizeKelompok($request->input('kelompok'));

        $pamongList = User::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
            ->with(['assignedStudents' => function ($query) use ($search, $schoolGrade, $kelompok) {
                $query->whereHas('siswa', function ($student) use ($search, $schoolGrade, $kelompok) {
                    $student->active()
                        ->when($schoolGrade, fn ($builder, $grade) => $builder->where('school_grade', $grade))
                        ->when($kelompok, fn ($builder, $group) => $builder->where('kelompok', $group))
                        ->when($search, fn ($builder, $term) => $builder->where(function ($nested) use ($term) {
                            $nested->where('nama', 'like', "%{$term}%")
                                ->orWhere('nis', 'like', "%{$term}%");
                        }));
                })->with([
                    'siswa:id,nis,nama,kelompok,school_grade,target_grade_override,tanggal_lahir,status,is_active',
                    'siswa.pamongAssignments.pamong:id,name,username',
                ]);
            }])
            ->when($selectedPamong, fn ($query, $id) => $query->whereKey($id))
            ->when($search, fn ($query, $term) => $query->where(function ($nested) use ($term) {
                $nested->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%")
                    ->orWhereHas('assignedStudents.siswa', fn ($student) => $student
                        ->active()
                        ->where(fn ($match) => $match->where('nama', 'like', "%{$term}%")
                            ->orWhere('nis', 'like', "%{$term}%")));
            }))
            ->orderByRaw('COALESCE(name, username)')
            ->get()
            ->filter(fn (User $pamong) => ! $search || $pamong->assignedStudents->isNotEmpty()
                || str_contains(mb_strtolower($pamong->name ?: $pamong->username), mb_strtolower($search)))
            ->values();

        $unassignedStudents = Siswa::query()
            ->active()
            ->doesntHave('pamongAssignments')
            ->when($schoolGrade, fn ($query, $grade) => $query->where('school_grade', $grade))
            ->when($kelompok, fn ($query, $group) => $query->where('kelompok', $group))
            ->when($search, fn ($query, $term) => $query->where(function ($nested) use ($term) {
                $nested->where('nama', 'like', "%{$term}%")->orWhere('nis', 'like', "%{$term}%");
            }))
            ->orderBy('nama')
            ->get(['id', 'nis', 'nama', 'kelompok', 'school_grade', 'target_grade_override', 'tanggal_lahir']);

        $uniqueAssignedIds = $pamongList->flatMap(fn (User $pamong) => $pamong->assignedStudents->pluck('siswa_id'))->unique();

        return view('kelas.index', [
            'pamongList' => $pamongList,
            'pamongOptions' => User::query()
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
                ->orderByRaw('COALESCE(name, username)')
                ->get(['id', 'name', 'username']),
            'unassignedStudents' => $unassignedStudents,
            'schoolGradeOptions' => TargetGrade::schoolClassOptions(),
            'kelompokOptions' => Siswa::kelompokOptions(),
            'totalPamong' => $pamongList->count(),
            'totalSiswa' => Siswa::active()->count(),
            'totalAssigned' => $uniqueAssignedIds->count(),
            'totalUnassigned' => Siswa::active()->doesntHave('pamongAssignments')->count(),
            'totalUnconfirmedGrade' => Siswa::active()->whereNull('school_grade')->count(),
        ]);
    }

    public function store(): JsonResponse
    {
        return $this->legacyMutationDisabled();
    }

    public function update(): JsonResponse
    {
        return $this->legacyMutationDisabled();
    }

    public function destroy(): JsonResponse
    {
        return $this->legacyMutationDisabled();
    }

    public function toggleStatus(): JsonResponse
    {
        return $this->legacyMutationDisabled();
    }

    public function getList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'deprecated' => true,
            'message' => 'Kelas lama hanya dipertahankan sebagai arsip. Gunakan kelas sekolah dan Binaan Pamong.',
            'data' => collect(TargetGrade::schoolClassOptions())->map(fn ($label, $value) => [
                'id' => $value,
                'value' => $value,
                'nama' => $label,
            ])->values(),
        ]);
    }

    private function legacyMutationDisabled(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'deprecated' => true,
            'message' => 'Manajemen kelas lama sudah dinonaktifkan. Atur siswa melalui Kelas Sekolah dan Binaan Pamong.',
        ], 410);
    }
}
