<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\PamongPermission;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Support\OperationalPermissionPreset;
use App\Support\PopupManager;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
        
        $generalSettings = Setting::getByGroup('general');
        $idCardSettings = Setting::getByGroup('id_card');
        if (strcasecmp(trim((string) ($idCardSettings['card_title'] ?? '')), 'KARTU PESERTA') === 0) {
            $idCardSettings['card_title'] = 'KARTU IDENTITAS';
        }
        $themeSettings = ThemeSetting::first() ?? new ThemeSetting;
        $defaultPermissions = self::getDefaultPamongPermissions();
        $permissionPresets = OperationalPermissionPreset::all();
        $availableMenus = \App\Models\PamongPermission::getAvailableMenus();
        $availableCrud = \App\Models\PamongPermission::getAvailableCrudOperations();
        $crudOperationLabels = \App\Models\PamongPermission::getCrudOperationLabels();
        $kelasSettings = Setting::getByGroup('kelas');
        $tingkatList = $kelasSettings['tingkat_list'] ?? 'X,XI,XII';

        // Load all tab data once so settings tabs can switch without a page refresh.
        $shareInfos = \App\Models\ShareInfo::with('creator')->orderByDesc('created_at')->get();
        $popupSettings = PopupManager::all();

        return view('settings.index', compact(
            'tab',
            'generalSettings',
            'idCardSettings',
            'themeSettings',
            'defaultPermissions',
            'permissionPresets',
            'availableMenus',
            'availableCrud',
            'crudOperationLabels',
            'kelasSettings',
            'tingkatList',
            'shareInfos',
            'popupSettings'
        ));
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
}
