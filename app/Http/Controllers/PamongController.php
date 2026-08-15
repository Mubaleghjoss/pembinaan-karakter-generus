<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalTeam;
use App\Models\PamongPermission;
use App\Models\PamongSiswa;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PamongAssignmentBoardService;
use App\Services\PamongAssignmentVersionConflict;
use App\Support\OperationalPermissionPreset;
use App\Support\TargetGrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PamongController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Display list of pamong with assigned students count.
     */
    public function index(Request $request)
    {
        $teams = OrganizationalTeam::query()
            ->withCount([
                'users as total_users_count',
                'users as active_users_count' => fn ($query) => $query->where('status', 'active'),
                'users as pkg_manager_count' => fn ($query) => $query->whereHas('role', fn ($role) => $role->where('name', User::ROLE_PKG_MANAGER)),
            ])
            ->with([
                'users' => fn ($query) => $query
                    ->select(['id', 'username', 'name', 'status', 'role_id', 'organizational_team_id', 'organizational_title', 'organizational_sort_order'])
                    ->with('role:id,name,display_name')
                    ->whereHas('role', fn ($role) => $role->whereIn('name', User::operationalRoleNames()))
                    ->orderBy('organizational_sort_order')
                    ->orderBy('name')
                    ->orderBy('username'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $editingTeam = null;
        if ($request->filled('edit_team')) {
            $editingTeam = OrganizationalTeam::find($request->integer('edit_team'));
        }
        $editingMember = null;
        if ($request->filled('edit_member')) {
            $editingMember = User::query()
                ->select(['id', 'username', 'name', 'status', 'role_id', 'organizational_team_id', 'organizational_title', 'organizational_sort_order'])
                ->with(['role:id,name,display_name', 'organizationalTeam:id,name'])
                ->whereHas('role', fn ($query) => $query->whereIn('name', User::operationalRoleNames()))
                ->find($request->integer('edit_member'));
        }

        $assignablePamong = User::query()
            ->select(['id', 'username', 'name', 'status', 'role_id', 'organizational_team_id', 'organizational_title', 'organizational_sort_order'])
            ->with(['role:id,name,display_name', 'organizationalTeam:id,name'])
            ->whereHas('role', fn ($query) => $query->whereIn('name', User::operationalRoleNames()))
            ->orderBy('name')
            ->orderBy('username')
            ->get();

        $availableMenus = PamongPermission::getAvailableMenus();
        $availableCrud = PamongPermission::getAvailableCrudOperations();
        $crudOperationLabels = PamongPermission::getCrudOperationLabels();
        $defaultPermissions = [
            'menu_permissions' => PamongPermission::getDefaultMenuPermissions(),
            'crud_permissions' => PamongPermission::getDefaultCrudPermissions(),
        ];

        return view('pamong.index', compact('teams', 'editingTeam', 'editingMember', 'assignablePamong', 'availableMenus', 'availableCrud', 'crudOperationLabels', 'defaultPermissions'));
    }

    /**
     * Get list of pamong for AJAX.
     */
    public function getList(Request $request)
    {
        $columns = ['id', 'username', 'name', 'email', 'status', 'role_id', 'avatar_path', 'organizational_team_id', 'organizational_title', 'organizational_sort_order'];

        if ($request->boolean('with_last_login')) {
            $columns[] = 'last_login_at';
        }

        if ($request->boolean('with_plain_password')) {
            $columns[] = 'plain_password';
        }

        $query = User::select($columns)
            ->with(['role:id,name,display_name', 'organizationalTeam:id,name,short_name,color_hex'])
            ->whereHas('role', fn($q) => $q->whereIn('name', User::operationalRoleNames()));

        if ($request->boolean('with_permissions')) {
            $query->with(['pamongPermission:id,user_id,menu_permissions,crud_permissions,is_excluded']);
        }

        if ($request->boolean('with_assigned_count')) {
            $query->withCount('assignedStudents');
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($teamId = $request->input('team_id')) {
            $query->where('organizational_team_id', $teamId);
        }

        $perPage = $request->input('per_page', 10);
        $pamong = $query->latest('id')->paginate($perPage);

        if ($request->boolean('with_plain_password')) {
            $pamong->getCollection()->each(fn (User $user) => $user->makeVisible('plain_password'));
        }

        return response()->json($pamong);
    }

    /**
     * Get assigned students for a pamong.
     */
    public function getAssignedStudents(User $pamong)
    {
        if (! $pamong->isTeacher()) {
            return response()->json(['students' => []]);
        }

        $students = $pamong->assignedStudents()
            ->with(['siswa.pamongAssignments.pamong:id,name,username'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->siswa->id,
                    'nis' => $assignment->siswa->nis,
                    'nama' => $assignment->siswa->nama,
                    'school_grade' => $assignment->siswa->school_grade,
                    'school_grade_label' => $assignment->siswa->school_grade_label,
                    'effective_pkg_level' => $assignment->siswa->target_grade_label,
                ];
            });

        return response()->json(['students' => $students]);
    }

    /**
     * Toggle pamong account status.
     */
    public function toggleStatus(User $pamong)
    {
        $newStatus = $pamong->status === 'active' ? 'inactive' : 'active';
        $pamong->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Status berhasil diubah',
        ]);
    }

    /**
     * Show pamong detail with assigned students.
     */
    public function show(User $pamong)
    {
        if (! $pamong->isTeacher()) {
            return redirect()->route('settings.index', ['tab' => 'pamong'])
                ->with('error', 'Penugasan siswa hanya tersedia untuk akun pamong.');
        }

        $pamong->loadCount('assignedStudents');

        $assignedStudents = $pamong->assignedStudents()
            ->with(['siswa.pamongAssignments.pamong:id,name,username'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        
        return view('pamong.show', compact('pamong', 'assignedStudents'));
    }

    /**
     * Show form to assign students to pamong.
     */
    public function assignForm(User $pamong, PamongAssignmentBoardService $boardService)
    {
        if (! $pamong->isTeacher()) {
            return redirect()->route('settings.index', ['tab' => 'pamong'])
                ->with('error', 'Penugasan siswa hanya tersedia untuk akun pamong.');
        }

        $pamongs = User::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', User::ROLE_TEACHER))
            ->orderByRaw('organizational_sort_order IS NULL')
            ->orderBy('organizational_sort_order')
            ->orderByRaw('COALESCE(name, username)')
            ->get(['id', 'name', 'username', 'avatar_path', 'organizational_sort_order']);
        $activePamongIds = $pamongs->pluck('id');
        $students = Siswa::query()
            ->active()
            ->with(['pamongAssignments' => fn ($query) => $query
                ->whereIn('pamong_id', $activePamongIds)
                ->select(['id', 'pamong_id', 'siswa_id'])])
            ->orderByRaw('school_grade IS NULL')
            ->orderBy('school_grade')
            ->orderBy('nama')
            ->get();
        $gradeOptions = TargetGrade::schoolClassOptions();
        $boardData = [
            'version' => $boardService->version(),
            'focused_pamong_id' => $pamongs->contains('id', $pamong->id) ? $pamong->id : null,
            'pamongs' => $pamongs->map(fn (User $item) => [
                'id' => $item->id,
                'name' => $item->display_name,
                'initials' => mb_strtoupper(mb_substr($item->display_name, 0, 1)),
            ])->values(),
            'students' => $students->map(fn (Siswa $student) => [
                'id' => $student->id,
                'name' => $student->nama,
                'nis' => $student->nis,
                'school_grade' => $student->school_grade,
                'school_grade_label' => $student->school_grade_label ?? 'Kelas belum dikonfirmasi',
                'kelompok' => $student->kelompok,
                'kelompok_label' => $student->kelompok_label ?? 'Kelompok belum diisi',
                'pamong_ids' => $student->pamongAssignments
                    ->pluck('pamong_id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values(),
            ])->values(),
        ];

        return view('pamong.assign', [
            'pamong' => $pamong,
            'boardData' => $boardData,
            'gradeOptions' => $gradeOptions,
            'kelompokOptions' => Siswa::kelompokOptions(),
            'totalPamong' => $pamongs->count(),
            'totalStudents' => $students->count(),
            'totalAssigned' => $students->filter(fn (Siswa $student) => $student->pamongAssignments->isNotEmpty())->count(),
            'totalUnassigned' => $students->filter(fn (Siswa $student) => $student->pamongAssignments->isEmpty())->count(),
        ]);
    }

    public function updateAssignmentBoard(
        Request $request,
        PamongAssignmentBoardService $boardService
    ): JsonResponse {
        $validated = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'students' => ['required', 'array', 'min:1', 'max:100'],
            'students.*.siswa_id' => ['required', 'integer', 'distinct', 'exists:siswa,id'],
            'students.*.pamong_ids' => [
                'present',
                'array',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_array($value)) {
                        return;
                    }

                    $normalizedPamongIds = array_map(
                        static fn (mixed $id): string => (string) $id,
                        $value
                    );

                    if (count($normalizedPamongIds) !== count(array_unique($normalizedPamongIds))) {
                        $fail('Pamong pada satu Generus tidak boleh dipilih lebih dari sekali.');
                    }
                },
            ],
            'students.*.pamong_ids.*' => ['integer', 'exists:users,id'],
        ]);

        try {
            $result = $boardService->update(
                $validated['students'],
                $request->user(),
                $validated['version']
            );
        } catch (PamongAssignmentVersionConflict $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'version' => $exception->currentVersion,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembagian Generus dan Pamong berhasil disimpan.',
            ...$result,
        ]);
    }

    /**
     * Assign students to pamong.
     */
    public function assignStudents(Request $request, User $pamong)
    {
        if (! $pamong->isTeacher()) {
            return redirect()->route('settings.index', ['tab' => 'pamong'])
                ->with('error', 'Penugasan siswa hanya tersedia untuk akun pamong.');
        }

        $validated = $request->validate([
            'siswa_ids' => 'nullable|array',
            'siswa_ids.*' => 'exists:siswa,id',
        ]);

        // Get current assignments
        $currentIds = $pamong->assignedStudents()->pluck('siswa_id')->toArray();
        $newIds = $validated['siswa_ids'] ?? [];

        if (Siswa::query()->whereIn('id', $newIds)->where(fn ($query) => $query
            ->where('status', '!=', 'active')
            ->orWhere('is_active', false))->exists()) {
            return back()->withErrors([
                'siswa_ids' => 'Penugasan Pamong hanya dapat diberikan kepada siswa berstatus Aktif.',
            ])->withInput();
        }

        // Add new assignments
        $toAdd = array_diff($newIds, $currentIds);
        foreach ($toAdd as $siswaId) {
            PamongSiswa::updateOrCreate(
                ['pamong_id' => $pamong->id, 'siswa_id' => $siswaId],
                ['ended_at' => null, 'ended_by' => null]
            );
        }

        // Remove unselected assignments
        $toRemove = array_diff($currentIds, $newIds);
        if (!empty($toRemove)) {
            PamongSiswa::where('pamong_id', $pamong->id)
                ->whereIn('siswa_id', $toRemove)
                ->whereNull('ended_at')
                ->update(['ended_at' => now(), 'ended_by' => auth()->id()]);
        }

        return redirect()->route('pamong.show', $pamong)
            ->with('success', 'Penugasan siswa berhasil diperbarui!');
    }

    /**
     * Remove student assignment from pamong.
     */
    public function removeAssignment(User $pamong, Siswa $siswa)
    {
        if (! $pamong->isTeacher()) {
            return redirect()->route('settings.index', ['tab' => 'pamong'])
                ->with('error', 'Penugasan siswa hanya tersedia untuk akun pamong.');
        }

        PamongSiswa::where('pamong_id', $pamong->id)
            ->where('siswa_id', $siswa->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now(), 'ended_by' => auth()->id()]);

        return back()->with('success', 'Siswa berhasil dihapus dari penugasan!');
    }

    /**
     * Get students by kelas for AJAX.
     */
    public function getStudentsByKelas(Request $request)
    {
        $kelasId = $request->get('kelas_id');
        
        $students = Siswa::where('kelas_id', $kelasId)
            ->active()
            ->orderBy('nama')
            ->get(['id', 'nis', 'nama']);

        return response()->json($students);
    }

    /**
     * Show QR generate page for pamong cards.
     */
    public function qrGenerate(Request $request)
    {
        // Check if ids parameter is provided (from AJAX call)
        if ($ids = $request->input('ids')) {
            $pamongIds = explode(',', $ids);
            
            $pamongList = User::whereIn('id', $pamongIds)
                ->whereHas('role', fn($q) => $q->whereIn('name', User::attendanceRoleNames()))
                ->orderBy('username')
                ->get();

            // Get QR data for each pamong
            $pamongQrService = app(\App\Services\Contracts\PamongQrServiceInterface::class);
            $pamongWithQr = $pamongList->map(function ($pamong) use ($pamongQrService) {
                $qrData = $pamongQrService->getQrData($pamong);
                return [
                    'user' => $pamong,
                    'qr_data' => $qrData,
                ];
            });

            return view('pamong.qr-print', [
                'pamongList' => $pamongWithQr,
            ]);
        }

        $pamongList = User::whereHas('role', fn($q) => $q->whereIn('name', User::attendanceRoleNames()))
            ->where('status', 'active')
            ->orderBy('username')
            ->get();

        return view('pamong.qr-generate', [
            'pamongList' => $pamongList,
        ]);
    }

    /**
     * Generate and print pamong cards.
     */
    public function qrGeneratePost(Request $request)
    {
        $validated = $request->validate([
            'pamong_ids' => 'required|array|min:1',
            'pamong_ids.*' => 'exists:users,id',
        ]);

        $pamongList = User::whereIn('id', $validated['pamong_ids'])
            ->whereHas('role', fn($q) => $q->whereIn('name', User::attendanceRoleNames()))
            ->orderBy('username')
            ->get();

        // Get QR data for each pamong
        $pamongQrService = app(\App\Services\Contracts\PamongQrServiceInterface::class);
        $pamongWithQr = $pamongList->map(function ($pamong) use ($pamongQrService) {
            $qrData = $pamongQrService->getQrData($pamong);
            return [
                'user' => $pamong,
                'qr_data' => $qrData,
            ];
        });

        return view('pamong.qr-print', [
            'pamongList' => $pamongWithQr,
        ]);
    }

    /**
     * Print active pamong ID cards without requiring manual QR regeneration.
     */
    public function printCards(Request $request)
    {
        $query = User::query()
            ->with('role:id,name,display_name')
            ->where('status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('name', User::operationalRoleNames()));

        if ($request->filled('ids')) {
            $ids = collect(explode(',', (string) $request->input('ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->values();

            $query->whereIn('id', $ids);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $pamongList = $query
            ->orderBy('name')
            ->orderBy('username')
            ->get();

        $pamongQrService = app(\App\Services\Contracts\PamongQrServiceInterface::class);
        $cards = $pamongList->map(fn (User $pamong) => [
            'user' => $pamong,
            'qr_data' => $pamongQrService->getQrData($pamong),
        ]);

        return view('pamong.cards-print', compact('cards', 'pamongList'));
    }

    /**
     * Show permission management form for a pamong.
     */
    public function permissionForm(User $pamong)
    {
        $pamong->load(['pamongPermission', 'role:id,name,display_name']);
        
        $availableMenus = PamongPermission::getAvailableMenus();
        $availableCrud = PamongPermission::getAvailableCrudOperations();
        $crudOperationLabels = PamongPermission::getCrudOperationLabels();
        $permissionPresets = OperationalPermissionPreset::all();
        
        // Other pamong users for "copy permissions" feature
        $otherPamong = User::whereHas('role', fn($q) => $q->whereIn('name', User::operationalRoleNames()))
            ->where('id', '!=', $pamong->id)
            ->where('status', 'active')
            ->orderBy('username')
            ->get(['id', 'username', 'email']);
        
        return view('pamong.permissions', compact('pamong', 'availableMenus', 'availableCrud', 'crudOperationLabels', 'otherPamong', 'permissionPresets'));
    }

    /**
     * Update pamong permissions.
     */
    public function updatePermissions(Request $request, User $pamong)
    {
        $validated = $request->validate([
            'is_excluded' => 'nullable|boolean',
            'menu_permissions' => 'nullable|array',
            'menu_permissions.*' => 'string',
            'crud_permissions' => 'nullable|array',
        ]);

        $permission = PamongPermission::updateOrCreate(
            ['user_id' => $pamong->id],
            [
                'is_excluded' => $validated['is_excluded'] ?? false,
                'menu_permissions' => $validated['menu_permissions'] ?? [],
                'crud_permissions' => PamongPermission::normalizeCrudPermissions($validated['crud_permissions'] ?? []),
            ]
        );

        return redirect()->route('pamong.permissions', $pamong)
            ->with('success', 'Hak akses akun tim berhasil diperbarui!');
    }

    /**
     * Copy permissions from this pamong to multiple other pamong.
     */
    public function copyPermissions(Request $request, User $pamong)
    {
        $validated = $request->validate([
            'target_ids' => 'required|array|min:1',
            'target_ids.*' => 'exists:users,id',
        ]);

        $source = $pamong->pamongPermission;

        if (!$source) {
            return redirect()->route('pamong.permissions', $pamong)
                ->with('error', 'Akun ini belum memiliki pengaturan hak akses.');
        }

        $count = 0;
        foreach ($validated['target_ids'] as $targetId) {
            if ($targetId == $pamong->id) continue;

            PamongPermission::updateOrCreate(
                ['user_id' => $targetId],
                [
                    'menu_permissions' => $source->menu_permissions,
                    'crud_permissions' => PamongPermission::normalizeCrudPermissions($source->crud_permissions ?? []),
                    'is_excluded' => $source->is_excluded,
                ]
            );
            $count++;
        }

        return redirect()->route('pamong.permissions', $pamong)
            ->with('success', "Hak akses berhasil disalin ke {$count} akun lainnya!");
    }

    /**
     * Show activity log for a pamong.
     */
    public function activityLog(Request $request, User $pamong)
    {
        $query = \App\Models\PamongActivityLog::where('user_id', $pamong->id)
            ->orderBy('created_at', 'desc');

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        $logs = $query->paginate(25);

        $actionLabels = \App\Models\PamongActivityLog::getActionLabels();
        $moduleLabels = \App\Models\PamongActivityLog::getModuleLabels();

        return view('pamong.activity-log', compact('pamong', 'logs', 'actionLabels', 'moduleLabels'));
    }

    /**
     * Get all pamong permissions for bulk management.
     */
    public function permissionsIndex()
    {
        $pamongList = User::whereHas('role', fn($q) => $q->whereIn('name', User::operationalRoleNames()))
            ->with(['pamongPermission', 'role:id,name,display_name', 'organizationalTeam:id,name,color_hex'])
            ->orderBy('username')
            ->get();
        
        $availableMenus = PamongPermission::getAvailableMenus();
        $availableCrud = PamongPermission::getAvailableCrudOperations();
        $crudOperationLabels = PamongPermission::getCrudOperationLabels();
        $permissionPresets = OperationalPermissionPreset::all();
        
        return view('pamong.permissions-index', compact('pamongList', 'availableMenus', 'availableCrud', 'crudOperationLabels', 'permissionPresets'));
    }

    public function storeTeam(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:organizational_teams,name',
            'short_name' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:1000',
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        OrganizationalTeam::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) ?: 'team-' . now()->timestamp,
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'color_hex' => $validated['color_hex'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang'])
            ->with('success', 'Bidang tim berhasil ditambahkan.');
    }

    public function updateTeam(Request $request, OrganizationalTeam $team)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('organizational_teams', 'name')->ignore($team->id)],
            'short_name' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:1000',
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $team->update([
            'name' => $validated['name'],
            'slug' => $team->slug ?: (Str::slug($validated['name']) ?: 'team-' . $team->id),
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'color_hex' => $validated['color_hex'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang'])
            ->with('success', 'Bidang tim berhasil diperbarui.');
    }

    public function destroyTeam(OrganizationalTeam $team)
    {
        if ($team->users()->exists()) {
            return redirect()->route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang'])
                ->with('error', 'Bidang tidak dapat dihapus karena masih dipakai oleh akun tim.');
        }

        $team->delete();

        return redirect()->route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang'])
            ->with('success', 'Bidang tim berhasil dihapus.');
    }

    public function saveTeamMember(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'organizational_team_id' => 'nullable|exists:organizational_teams,id',
            'organizational_title' => 'nullable|string|max:120',
            'organizational_sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $user = User::query()
            ->with(['role:id,name', 'organizationalTeam:id,name'])
            ->findOrFail($validated['user_id']);

        if (! $user->usesPamongPermissionSystem()) {
            return redirect()->route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang'])
                ->with('error', 'Akun yang dipilih tidak termasuk akun pamong atau pengurus PKG.');
        }

        $team = null;
        if (! empty($validated['organizational_team_id'])) {
            $team = OrganizationalTeam::find($validated['organizational_team_id']);
        }

        $user->update([
            'organizational_team_id' => $team?->id,
            'organizational_title' => $validated['organizational_title'] ?: null,
            'organizational_sort_order' => $validated['organizational_sort_order'] ?? 0,
        ]);

        $message = $team
            ? "Akun {$user->username} berhasil ditempatkan ke bidang {$team->name}."
            : "Bidang akun {$user->username} berhasil dikosongkan.";

        return redirect()->route('settings.index', ['tab' => 'pamong', 'pamong_tab' => 'bidang'])
            ->with('success', $message);
    }

    /**
     * Reset password for a pamong.
     */
    public function resetPassword(User $pamong)
    {
        // Generate new password (default: username)
        $newPassword = $pamong->username;
        
        $pamong->update([
            'password' => Hash::make($newPassword),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Password berhasil direset!",
                'new_password' => $newPassword,
            ]);
        }

        return back()->with('success', "Password berhasil direset! Password baru: {$newPassword}");
    }

    /**
     * Change password for a pamong (AJAX).
     */
    public function changePassword(Request $request, User $pamong)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:6',
        ], [
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password minimal 6 karakter.',
        ]);

        $pamong->update([
            'password' => Hash::make($validated['new_password']),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah!',
        ]);
    }

    /**
     * Reset password with custom password.
     */
    public function resetPasswordCustom(Request $request, User $pamong)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $pamong->update([
            'password' => Hash::make($validated['new_password']),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        return back()->with('success', 'Password berhasil diubah!');
    }

    /**
     * Export all pamong account data to Excel (.xlsx).
     */
    public function exportAccounts()
    {
        $pamongList = User::whereHas('role', fn($q) => $q->whereIn('name', User::attendanceRoleNames()))
            ->select(['id', 'username', 'name', 'email', 'phone', 'plain_password', 'status', 'role_id', 'organizational_team_id', 'organizational_title'])
            ->with(['role:id,name,display_name', 'organizationalTeam:id,name'])
            ->withCount('assignedStudents')
            ->orderBy('username')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Akun Pamong');

        // Header columns
        $headers = ['No', 'Username', 'Nama', 'Email', 'No. Telepon', 'Password', 'Status', 'Bidang', 'Jabatan', 'Jumlah Siswa Binaan'];
        $lastCol = chr(64 + count($headers));

        foreach ($headers as $colIndex => $header) {
            $col = chr(65 + $colIndex);
            $sheet->setCellValue("{$col}1", $header);
        }

        // Header style: bold, white text, blue background
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Data rows
        foreach ($pamongList as $index => $pamong) {
            $row = $index + 2;
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $pamong->username);
            $sheet->setCellValue("C{$row}", $pamong->name ?? $pamong->username);
            $sheet->setCellValue("D{$row}", $pamong->email ?? '-');
            $sheet->setCellValueExplicit("E{$row}", $pamong->phone ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$row}", $pamong->plain_password ?? 'Tidak tersimpan', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("G{$row}", $pamong->status === 'active' ? 'Aktif' : 'Tidak Aktif');
            $sheet->setCellValue("H{$row}", $pamong->organizationalTeam?->name ?? '-');
            $sheet->setCellValue("I{$row}", $pamong->organizational_title ?? '-');
            $sheet->setCellValue("J{$row}", $pamong->assigned_students_count);
        }

        // Borders for all data (header + rows)
        $lastRow = count($pamongList) + 1;
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray($borderStyle);

        // Zebra striping for data rows
        for ($r = 2; $r <= $lastRow; $r++) {
            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9E2F3');
            }
        }

        // Auto-width columns
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Center 'No' and 'Jumlah Siswa Binaan' columns
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $filename = 'data_akun_pamong_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Bulk update pamong permissions.
     */
    public function bulkUpdatePermissions(Request $request)
    {
        $validated = $request->validate([
            'pamong_ids' => 'required|array',
            'pamong_ids.*' => 'exists:users,id',
            'action' => 'required|in:allow_all,restrict_all,set_excluded,remove_excluded,set_menus,set_crud,set_default,set_custom,apply_preset',
            'menu_permissions' => 'nullable|array',
            'crud_permissions' => 'nullable|array',
            'preset_key' => 'nullable|string',
        ]);

        if (($validated['action'] ?? null) === 'apply_preset' && ! OperationalPermissionPreset::find($validated['preset_key'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Paket izin yang dipilih tidak valid.',
            ], 422);
        }

        foreach ($validated['pamong_ids'] as $pamongId) {
            $permission = PamongPermission::firstOrCreate(
                ['user_id' => $pamongId],
                [
                    'menu_permissions' => PamongPermission::getDefaultMenuPermissions(), 
                    'crud_permissions' => PamongPermission::getDefaultCrudPermissions(),
                    'is_excluded' => false
                ]
            );

            switch ($validated['action']) {
                case 'allow_all':
                    $permission->update([
                        'menu_permissions' => array_keys(PamongPermission::getAvailableMenus()),
                        'crud_permissions' => PamongPermission::getAvailableCrudOperations(),
                        'is_excluded' => false,
                    ]);
                    break;
                case 'restrict_all':
                    $permission->update([
                        'menu_permissions' => [],
                        'crud_permissions' => [],
                        'is_excluded' => false,
                    ]);
                    break;
                case 'set_default':
                    $permission->update([
                        'menu_permissions' => PamongPermission::getDefaultMenuPermissions(),
                        'crud_permissions' => PamongPermission::getDefaultCrudPermissions(),
                        'is_excluded' => false,
                    ]);
                    break;
                case 'set_excluded':
                    $permission->update(['is_excluded' => true]);
                    break;
                case 'remove_excluded':
                    $permission->update(['is_excluded' => false]);
                    break;
                case 'set_menus':
                    $permission->update([
                        'menu_permissions' => $validated['menu_permissions'] ?? [],
                        'is_excluded' => false,
                    ]);
                    break;
                case 'set_crud':
                    $permission->update([
                        'crud_permissions' => PamongPermission::normalizeCrudPermissions($validated['crud_permissions'] ?? []),
                        'is_excluded' => false,
                    ]);
                    break;
                case 'set_custom':
                    $permission->update([
                        'menu_permissions' => $validated['menu_permissions'] ?? [],
                        'crud_permissions' => PamongPermission::normalizeCrudPermissions($validated['crud_permissions'] ?? []),
                        'is_excluded' => false,
                    ]);
                    break;
                case 'apply_preset':
                    $presetPermissions = OperationalPermissionPreset::permissionsFor($validated['preset_key'] ?? null);
                    if ($presetPermissions) {
                        $permission->update([
                            'menu_permissions' => $presetPermissions['menu_permissions'],
                            'crud_permissions' => $presetPermissions['crud_permissions'],
                            'is_excluded' => false,
                        ]);
                    }
                    break;
            }
        }

        return response()->json(['success' => true, 'message' => 'Hak akses berhasil diperbarui!']);
    }
}
