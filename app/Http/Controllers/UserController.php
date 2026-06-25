<?php

namespace App\Http\Controllers;

use App\Imports\UserImport;
use App\Models\OrganizationalTeam;
use App\Models\PamongPermission;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\PamongQrServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    public function __construct(
        protected PamongQrServiceInterface $pamongQrService
    ) {}
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with('role')
            ->withCount(['validBiometricCredentials', 'legacyBiometricCredentials']);

        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::whereIn('name', [User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_PKG_MANAGER])->get();
        $teams = OrganizationalTeam::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('users.create', compact('roles', 'teams'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'organizational_team_id' => 'nullable|exists:organizational_teams,id',
            'organizational_title' => 'nullable|string|max:120',
            'organizational_sort_order' => 'nullable|integer|min:0|max:9999',
            'status' => 'required|in:active,inactive',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['organizational_team_id'] = $validated['organizational_team_id'] ?? null;
        $validated['organizational_title'] = $validated['organizational_title'] ?? null;
        $validated['organizational_sort_order'] = $validated['organizational_sort_order'] ?? 0;

        $plainPassword = $validated['password'];
        $validated['password'] = Hash::make($plainPassword);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = $avatarPath;
        }
        unset($validated['avatar']);

        $user = User::create($validated);

        $this->syncOperationalPermissions($user);

        // Auto-generate QR token untuk pamong/teacher
        if ($this->pamongQrService->isPamong($user)) {
            $this->pamongQrService->generateToken($user);
        }

        return redirect()->route('settings.index', ['tab' => 'user'])
            ->with('success', 'User berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $roles = Role::whereIn('name', [User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_PKG_MANAGER])->get();
        $teams = OrganizationalTeam::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('users.edit', compact('user', 'roles', 'teams'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'organizational_team_id' => 'nullable|exists:organizational_teams,id',
            'organizational_title' => 'nullable|string|max:120',
            'organizational_sort_order' => 'nullable|integer|min:0|max:9999',
            'status' => 'required|in:active,inactive',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['organizational_team_id'] = $validated['organizational_team_id'] ?? null;
        $validated['organizational_title'] = $validated['organizational_title'] ?? null;
        $validated['organizational_sort_order'] = $validated['organizational_sort_order'] ?? 0;

        // Check last admin protection
        $nextRole = Role::find($validated['role_id']);
        $demotingLastAdmin = $user->isAdmin() && $nextRole?->name !== User::ROLE_ADMIN;

        if ($user->isAdmin() && ($validated['status'] === 'inactive' || $demotingLastAdmin)) {
            if ($this->isLastActiveAdmin($user->id)) {
                return back()->withErrors([
                    'status' => $demotingLastAdmin
                        ? 'Tidak dapat mengubah role admin terakhir ke role lain.'
                        : 'Tidak dapat menonaktifkan admin terakhir.',
                ]);
            }
        }

        if (!empty($validated['password'])) {
            $plainPassword = $validated['password'];
            $validated['password'] = Hash::make($plainPassword);
        } else {
            unset($validated['password']);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = $avatarPath;
        }
        unset($validated['avatar']);

        $user->update($validated);
        $user->refresh();

        $this->syncOperationalPermissions($user);

        return redirect()->route('settings.index', ['tab' => 'user'])
            ->with('success', 'User berhasil diperbarui!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Check last admin protection
        if ($user->hasRole('admin')) {
            if ($this->isLastActiveAdmin($user->id)) {
                return back()->withErrors(['error' => 'Tidak dapat menghapus admin terakhir.']);
            }
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $user->delete();

        return redirect()->route('settings.index', ['tab' => 'user'])
            ->with('success', 'User berhasil dihapus!');
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';

        // Check last admin protection
        if ($user->hasRole('admin') && $newStatus === 'inactive') {
            if ($this->isLastActiveAdmin($user->id)) {
                return back()->withErrors(['error' => 'Tidak dapat menonaktifkan admin terakhir.']);
            }
        }

        $user->update(['status' => $newStatus]);

        return back()->with('success', 'Status user berhasil diubah!');
    }

    /**
     * Download template Excel untuk import pamong.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        
        // Sheet 1: Data
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pamong');
        
        // Headers
        $headers = ['Username', 'Email', 'Password', 'Status'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        
        // Sample data
        $sampleData = [
            ['pamong_budi', 'budi@sekolah.com', 'password123', 'active'],
            ['pamong_ani', 'ani@sekolah.com', 'password123', 'active'],
            ['pamong_citra', 'citra@sekolah.com', '', 'active'],
        ];
        $sheet->fromArray($sampleData, null, 'A2');
        
        // Auto width
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Sheet 2: Petunjuk
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Petunjuk');
        
        $instructions = [
            ['PETUNJUK PENGISIAN DATA PAMONG'],
            [''],
            [''],
            ['Username', 'Username untuk login (wajib, unik)'],
            ['Email', 'Email pamong (wajib, unik)'],
            ['Password', 'Password untuk login (opsional, default: pamong123)'],
            ['Status', 'Status akun: active atau inactive (opsional, default: active)'],
            [''],
            [''],
            ['CATATAN PENTING:'],
            ['1. Hapus baris contoh sebelum mengisi data'],
            ['2. Username dan Email harus unik dan belum terdaftar'],
            ['3. Jika password kosong, akan menggunakan default: pamong123'],
            ['4. Semua pamong yang diimport akan memiliki role Pamong'],
        ];
        
        $instructionSheet->fromArray($instructions, null, 'A1');
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructionSheet->getStyle('A10')->getFont()->setBold(true);
        $instructionSheet->getColumnDimension('A')->setWidth(20);
        $instructionSheet->getColumnDimension('B')->setWidth(60);
        
        $spreadsheet->setActiveSheetIndex(0);
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'template_import_pamong.xlsx';
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import pamong dari file Excel.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            // Ensure temp directory exists
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Store uploaded file
            $file = $request->file('file');
            $fileName = 'import_user_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($tempDir, $fileName);
            $fullPath = $tempDir . '/' . $fileName;

            // Process import
            $importer = new UserImport();
            $result = $importer->import($fullPath);

            // Cleanup
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }

            if ($result['failed'] > 0) {
                return back()
                    ->with('warning', "Import selesai. Berhasil: {$result['success']}, Gagal: {$result['failed']}")
                    ->with('import_errors', $result['errors']);
            }

            return back()->with('success', "Import berhasil! {$result['success']} pamong ditambahkan.");
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Gagal mengimport data: ' . $e->getMessage()]);
        }
    }

    protected function isLastActiveAdmin(?int $excludingUserId = null): bool
    {
        $query = User::whereHas('role', fn($q) => $q->where('name', User::ROLE_ADMIN))
            ->where('status', 'active');

        if ($excludingUserId) {
            $query->where('id', '!=', $excludingUserId);
        }

        return ! $query->exists();
    }

    protected function syncOperationalPermissions(User $user): void
    {
        if (! $user->usesPamongPermissionSystem()) {
            return;
        }

        $defaultPermissions = SettingsController::getDefaultPamongPermissions();

        PamongPermission::firstOrCreate(
            ['user_id' => $user->id],
            [
                'menu_permissions' => $defaultPermissions['menu_permissions'] ?? PamongPermission::getDefaultMenuPermissions(),
                'crud_permissions' => $defaultPermissions['crud_permissions'] ?? [],
                'is_excluded' => false,
            ]
        );
    }
}
