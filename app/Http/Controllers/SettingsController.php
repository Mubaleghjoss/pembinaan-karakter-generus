<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\GenerusRegistrationInvite;
use App\Models\PamongPermission;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Support\FaceAttendanceConfig;
use App\Support\OperationalPermissionPreset;
use App\Support\PopupManager;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Display settings page with tabs.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'general');

        if ($tab === 'user' || $tab === 'users') {
            $usersQuery = User::with('role')
                ->withCount(['validBiometricCredentials', 'legacyBiometricCredentials']);

            if ($request->filled('role')) {
                $usersQuery->whereHas('role', fn ($query) => $query->where('name', $request->role));
            }

            if ($request->filled('status')) {
                $usersQuery->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $usersQuery->where(function ($query) use ($search) {
                    $query->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $usersQuery->orderByDesc('created_at')->paginate(15);
            $roles = \App\Models\Role::all();
            $tab = 'user';

            return view('users.index', compact('users', 'roles', 'tab'));
        }

        if ($tab === 'pamong') {
            $teams = \App\Models\OrganizationalTeam::query()
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
                $editingTeam = \App\Models\OrganizationalTeam::find($request->integer('edit_team'));
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

            $availableMenus = \App\Models\PamongPermission::getAvailableMenus();
            $availableCrud = \App\Models\PamongPermission::getAvailableCrudOperations();
            $crudOperationLabels = \App\Models\PamongPermission::getCrudOperationLabels();
            $defaultPermissions = self::getDefaultPamongPermissions();

            return view('pamong.index', compact('teams', 'editingTeam', 'editingMember', 'assignablePamong', 'availableMenus', 'availableCrud', 'crudOperationLabels', 'defaultPermissions', 'tab'));
        }

        if ($tab === 'backup') {
            $backups = app(BackupService::class)->getBackupList();

            return view('settings.backup', compact('backups', 'tab'));
        }

        // Tab "Daftar Ulang" mengarah ke halaman khusus (tabel WA + status surat pernyataan).
        if ($tab === 'daftarulang' || $tab === 'daftar_ulang') {
            return redirect()->route('admin.generus-registration.index');
        }
        
        $validTabs = ['general', 'id_card', 'theme', 'kelas', 'permissions', 'share_info', 'face_attendance', 'popup', 'registration'];
        $tab = in_array($tab, $validTabs, true) ? $tab : 'general';
        $viewData = ['tab' => $tab];

        switch ($tab) {
            case 'general':
                $viewData['generalSettings'] = Setting::getByGroup('general');
                $viewData['themeSettings'] = ThemeSetting::first() ?? new ThemeSetting;
                break;

            case 'id_card':
                $viewData['idCardSettings'] = Setting::getByGroup('id_card');
                if (strcasecmp(trim((string) ($viewData['idCardSettings']['card_title'] ?? '')), 'KARTU PESERTA') === 0) {
                    $viewData['idCardSettings']['card_title'] = 'KARTU IDENTITAS';
                }
                break;

            case 'theme':
                $viewData['themeSettings'] = ThemeSetting::first() ?? new ThemeSetting;
                break;

            case 'kelas':
                $kelasSettings = Setting::getByGroup('kelas');
                $viewData['tingkatList'] = $kelasSettings['tingkat_list'] ?? 'X,XI,XII';
                break;

            case 'permissions':
                $viewData['defaultPermissions'] = self::getDefaultPamongPermissions();
                $viewData['permissionPresets'] = OperationalPermissionPreset::all();
                $viewData['availableMenus'] = PamongPermission::getAvailableMenus();
                $viewData['availableCrud'] = PamongPermission::getAvailableCrudOperations();
                $viewData['crudOperationLabels'] = PamongPermission::getCrudOperationLabels();
                $viewData['permissionAccounts'] = self::buildPermissionAccountSummary();
                break;

            case 'share_info':
                $viewData['shareInfos'] = \App\Models\ShareInfo::with('creator')->orderByDesc('created_at')->get();
                break;

            case 'face_attendance':
                $viewData['faceAttendanceSettings'] = FaceAttendanceConfig::all();
                break;

            case 'popup':
                $viewData['popupSettings'] = PopupManager::all();
                break;

            case 'registration':
                $viewData['registrationInvite'] = GenerusRegistrationInvite::query()->latest('id')->first();
                break;
        }

        return view('settings.index', $viewData);
    }

    /**
     * Update the private registration access code and its limits.
     */
    public function updateRegistrationAccess(Request $request)
    {
        $registrationInvite = GenerusRegistrationInvite::query()->latest('id')->first();
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'access_code' => [
                $registrationInvite ? 'nullable' : 'required',
                'string',
                'min:6',
                'max:32',
                'regex:/^[A-Za-z0-9]+$/',
            ],
            'valid_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'max_uses' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'access_code.regex' => 'Kode akses hanya boleh berisi huruf dan angka.',
        ]);

        if ($registrationInvite && (int) $validated['max_uses'] < $registrationInvite->used_count) {
            throw ValidationException::withMessages([
                'max_uses' => "Kuota maksimal tidak boleh kurang dari jumlah penggunaan saat ini ({$registrationInvite->used_count}).",
            ]);
        }

        $plainCode = null;
        if (! empty($validated['access_code'])) {
            $plainCode = Str::upper($validated['access_code']);
            $tokenHash = hash('sha256', $plainCode);
            $duplicateCode = GenerusRegistrationInvite::query()
                ->where('token_hash', $tokenHash)
                ->when($registrationInvite, fn ($query) => $query->where('id', '!=', $registrationInvite->id))
                ->exists();

            if ($duplicateCode) {
                throw ValidationException::withMessages([
                    'access_code' => 'Kode akses tersebut sudah digunakan oleh undangan lain.',
                ]);
            }
        }

        $registrationInvite ??= new GenerusRegistrationInvite(['used_count' => 0]);
        $registrationInvite->label = trim($validated['label']);
        $registrationInvite->max_uses = (int) $validated['max_uses'];
        $registrationInvite->expires_at = now()->addDays((int) $validated['valid_days']);
        $registrationInvite->is_active = $request->boolean('is_active');
        if ($plainCode !== null) {
            $registrationInvite->token_hash = hash('sha256', $plainCode);
        }
        $registrationInvite->save();

        $redirect = redirect()->route('settings.index', ['tab' => 'registration'])
            ->with('success', 'Pengaturan akses pendaftaran PKG berhasil disimpan.');

        return $plainCode === null
            ? $redirect
            : $redirect->with('registration_access_code', $plainCode);
    }

    /**
     * Update general website settings.
     */
    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_title' => 'required|string|max:255',
            'site_name' => 'required|string|max:255',
            'primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'site_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'site_favicon' => 'nullable|file|mimes:png,jpg,jpeg,svg,ico,webp|max:1024',
        ]);

        // Get or create theme settings
        $themeSettings = ThemeSetting::first() ?? new ThemeSetting();

        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            // Delete old logo if exists
            if ($themeSettings->logo_path && Storage::disk('public')->exists($themeSettings->logo_path)) {
                Storage::disk('public')->delete($themeSettings->logo_path);
            }
            
            $logo = $request->file('site_logo');
            $path = $logo->store('logos', 'public');
            $themeSettings->logo_path = $path;
            
            // Also save to Setting for backward compatibility
            Setting::set('site_logo', $path, 'general');
        }

        if ($request->hasFile('site_favicon')) {
            if ($themeSettings->favicon_path && Storage::disk('public')->exists($themeSettings->favicon_path)) {
                Storage::disk('public')->delete($themeSettings->favicon_path);
            }

            $favicon = $request->file('site_favicon');
            $themeSettings->favicon_path = $favicon->store('favicons', 'public');
        }

        // Update ThemeSetting
        $themeSettings->app_name = $validated['site_title'];
        $themeSettings->app_description = $validated['site_name'];
        $themeSettings->primary_color = $validated['primary_color'];
        $themeSettings->save();

        // Also save to Setting for backward compatibility
        Setting::set('site_title', $validated['site_title'], 'general');
        Setting::set('site_name', $validated['site_name'], 'general');
        Setting::set('primary_color', $validated['primary_color'], 'general');

        Cache::forget(ThemeSetting::CACHE_KEY);

        return redirect()->route('settings.index', ['tab' => 'general'])
            ->with('success', 'Pengaturan website berhasil disimpan!');
    }

    /**
     * Update ID card settings.
     */
    public function updateIdCard(Request $request)
    {
        $validated = $request->validate([
            'card_title' => 'required|string|max:255',
            'card_subtitle' => 'required|string|max:255',
            'card_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'card_footer_text' => 'nullable|string|max:255',
            'card_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:1024',
        ]);

        // Handle logo upload
        if ($request->hasFile('card_logo')) {
            $logo = $request->file('card_logo');
            $path = $logo->store('logos', 'public');
            Setting::set('card_logo', $path, 'id_card');
        }

        Setting::set('card_title', $validated['card_title'], 'id_card');
        Setting::set('card_subtitle', $validated['card_subtitle'], 'id_card');
        Setting::set('card_color', $validated['card_color'], 'id_card');
        Setting::set('card_footer_text', $validated['card_footer_text'] ?? '', 'id_card');

        return redirect()->route('settings.index', ['tab' => 'id_card'])
            ->with('success', 'Pengaturan ID Card berhasil disimpan!');
    }

    /**
     * Update theme settings (legacy support).
     */
    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_description' => 'nullable|string|max:500',
            'primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'success_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'warning_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'danger_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'dark_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'light_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'sidebar_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'topbar_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
        ]);

        $settings = ThemeSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            ThemeSetting::create($validated);
        }

        Cache::forget(ThemeSetting::CACHE_KEY);

        return redirect()->route('settings.index', ['tab' => 'theme'])
            ->with('success', 'Pengaturan tema berhasil disimpan!');
    }

    /**
     * Legacy update method for backward compatibility.
     */
    public function update(Request $request)
    {
        return $this->updateTheme($request);
    }

    /**
     * Update footer settings.
     */
    public function updateFooter(Request $request)
    {
        $validated = $request->validate([
            'footer_text' => 'nullable|string|max:255',
            'footer_organization' => 'nullable|string|max:255',
            'footer_address' => 'nullable|string|max:500',
            'footer_phone' => 'nullable|string|max:50',
            'footer_email' => 'nullable|email|max:255',
        ]);

        $settings = ThemeSetting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            ThemeSetting::create($validated);
        }

        Cache::forget(ThemeSetting::CACHE_KEY);

        return redirect()->route('settings.index', ['tab' => 'general'])
            ->with('success', 'Pengaturan footer berhasil disimpan!');
    }

    /**
     * Update tingkat/level kelas settings.
     */
    public function updateTingkat(Request $request)
    {
        $validated = $request->validate([
            'tingkat_list' => 'required|string',
        ]);

        // Parse tingkat list (format: "X,XI,XII" atau "7,8,9")
        $tingkatList = array_filter(array_map('trim', explode(',', $validated['tingkat_list'])));
        
        Setting::set('tingkat_list', implode(',', $tingkatList), 'kelas');

        return redirect()->route('settings.index', ['tab' => 'kelas'])
            ->with('success', 'Pengaturan tingkat kelas berhasil disimpan!');
    }

    /**
     * Get tingkat list as array.
     */
    public static function getTingkatList(): array
    {
        $tingkatStr = Setting::get('tingkat_list', 'X,XI,XII');
        return array_filter(array_map('trim', explode(',', $tingkatStr)));
    }

    /**
     * Update default pamong permissions.
     */
    public function updateDefaultPermissions(Request $request)
    {
        $validated = $request->validate([
            'menu_permissions' => 'required|array',
            'menu_permissions.*' => 'string',
            'crud_permissions' => 'nullable|array',
        ]);

        // Save as JSON in settings
        Setting::set('default_pamong_menu_permissions', json_encode($validated['menu_permissions']), 'permissions');
        Setting::set(
            'default_pamong_crud_permissions',
            json_encode(PamongPermission::normalizeCrudPermissions($validated['crud_permissions'] ?? [])),
            'permissions'
        );

        // Clear cache
        Cache::forget('default_pamong_permissions');
        Cache::forget('default_pamong_permissions_model');

        return redirect()->route('settings.index', ['tab' => 'permissions'])
            ->with('success', 'Pengaturan default hak akses pamong berhasil disimpan!');
    }

    /**
     * Create or update a custom operational permission preset.
     */
    public function storePreset(Request $request)
    {
        $validated = $request->validate([
            'preset_key' => 'nullable|string|max:64',
            'label' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'menu_permissions' => 'required|array|min:1',
            'menu_permissions.*' => 'string',
            'crud_permissions' => 'nullable|array',
        ]);

        $key = OperationalPermissionPreset::save(
            $validated['preset_key'] ?? null,
            $validated['label'],
            $validated['description'] ?? null,
            $validated['menu_permissions'],
            $validated['crud_permissions'] ?? []
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Paket izin berhasil disimpan.',
                'preset_key' => $key,
                'presets' => OperationalPermissionPreset::all(),
            ]);
        }

        return redirect()->route('settings.index', ['tab' => 'permissions'])
            ->with('success', 'Paket izin berhasil disimpan.');
    }

    /**
     * Delete a custom preset (or revert a built-in override).
     */
    public function destroyPreset(Request $request)
    {
        $validated = $request->validate([
            'preset_key' => 'required|string|max:64',
        ]);

        $deleted = OperationalPermissionPreset::delete($validated['preset_key']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted
                    ? 'Paket izin dihapus.'
                    : 'Paket bawaan tanpa perubahan tidak dapat dihapus.',
                'presets' => OperationalPermissionPreset::all(),
            ], $deleted ? 200 : 422);
        }

        return redirect()->route('settings.index', ['tab' => 'permissions'])
            ->with($deleted ? 'success' : 'error', $deleted
                ? 'Paket izin dihapus.'
                : 'Paket bawaan tanpa perubahan tidak dapat dihapus.');
    }

    /**
     * Update automatic popup settings.
     */
    public function updatePopups(Request $request)
    {
        $popups = $request->input('popups', []);

        foreach (PopupManager::definitions() as $popupKey => $definition) {
            $enabled = isset($popups[$popupKey]['enabled']);
            $required = isset($popups[$popupKey]['required']);

            PopupManager::setConfig($popupKey, $enabled, $required);
        }

        return redirect()->route('settings.index', ['tab' => 'popup'])
            ->with('success', 'Pengaturan popup berhasil disimpan!');
    }

    public function updateFaceAttendance(Request $request)
    {
        $validated = $request->validate([
            'enabled_siswa' => ['nullable', 'boolean'],
            'enabled_pamong' => ['nullable', 'boolean'],
            'center_lat' => ['required', 'numeric', 'between:-90,90'],
            'center_lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_value' => ['required', 'numeric', 'min:1', 'max:1000'],
            'radius_unit' => ['required', 'string', 'in:meter,kilometer'],
            'match_threshold' => ['required', 'numeric', 'min:20', 'max:100'],
            'max_accuracy_meters' => ['required', 'integer', 'min:5', 'max:5000'],
        ]);

        FaceAttendanceConfig::store([
            'enabled_siswa' => $request->boolean('enabled_siswa'),
            'enabled_pamong' => $request->boolean('enabled_pamong'),
            'center_lat' => $validated['center_lat'],
            'center_lng' => $validated['center_lng'],
            'radius_value' => $validated['radius_value'],
            'radius_unit' => $validated['radius_unit'],
            'match_threshold' => $validated['match_threshold'],
            'max_accuracy_meters' => $validated['max_accuracy_meters'],
        ]);

        return redirect()->route('settings.index', ['tab' => 'face_attendance'])
            ->with('success', 'Pengaturan scan wajah berhasil disimpan!');
    }

    /**
     * Get default pamong permissions.
     */
    public static function getDefaultPamongPermissions(): array
    {
        return Cache::remember('default_pamong_permissions', 3600, function () {
            $menuPermissions = Setting::get('default_pamong_menu_permissions', null, 'permissions');
            $crudPermissions = Setting::get('default_pamong_crud_permissions', null, 'permissions');
            $decodedCrudPermissions = $crudPermissions ? json_decode($crudPermissions, true) : null;

            return [
                'menu_permissions' => $menuPermissions ? json_decode($menuPermissions, true) : PamongPermission::FALLBACK_DEFAULT_MENU_PERMISSIONS,
                'crud_permissions' => is_array($decodedCrudPermissions)
                    ? PamongPermission::normalizeCrudPermissions($decodedCrudPermissions)
                    : PamongPermission::FALLBACK_DEFAULT_CRUD_PERMISSIONS,
            ];
        });
    }

    /**
     * Build a minimal per-account access summary for the permissions tab:
     * who each operational account is, its access status, and which menus it can reach.
     */
    protected static function buildPermissionAccountSummary(): array
    {
        $availableMenus = PamongPermission::getAvailableMenus();
        $defaultMenus = PamongPermission::getDefaultMenuPermissions();

        $accounts = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('name', User::operationalRoleNames()))
            ->with(['pamongPermission', 'role:id,name,display_name', 'organizationalTeam:id,name'])
            ->orderBy('username')
            ->get();

        return $accounts->map(function (User $user) use ($availableMenus, $defaultMenus) {
            $permission = $user->pamongPermission;

            if ($permission && $permission->is_excluded) {
                $status = 'full';
                $menuKeys = array_keys($availableMenus);
            } elseif ($permission) {
                $status = 'limited';
                $menuKeys = is_array($permission->menu_permissions) ? $permission->menu_permissions : [];
            } else {
                $status = 'default';
                $menuKeys = $defaultMenus;
            }

            $menuLabels = [];
            foreach ($menuKeys as $key) {
                if ($key === 'dashboard') {
                    continue; // dashboard is universal, keep the list focused on real capabilities
                }
                $menuLabels[] = $availableMenus[$key] ?? $key;
            }

            return [
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role_label' => $user->operationalRoleLabel(),
                'team' => $user->organizationalTeam?->name,
                'status' => $status,
                'menu_labels' => $menuLabels,
                'menu_count' => count($menuLabels),
                'edit_url' => route('pamong.permissions', $user),
            ];
        })->all();
    }
}
