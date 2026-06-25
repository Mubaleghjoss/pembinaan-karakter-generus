<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPORT_MENU = 'laporan_penyaksian';

    private const LEGACY_DEFAULT_MENU_SETS = [
        ['dashboard', 'materi', 'calendar'],
        ['dashboard', 'materi', 'calendar', 'manual_attendance'],
    ];

    private const DEFAULT_REPORT_CRUD = ['view', 'tindak_lanjut'];

    public function up(): void
    {
        $this->appendReportMenuToDefaultSettings();
        $this->appendReportMenuToLegacyDefaultPermissionRows();

        Cache::forget('app_settings');
        Cache::forget('default_pamong_permissions');
        Cache::forget('default_pamong_permissions_model');
    }

    public function down(): void
    {
        // Data migration only. Keep existing permission choices intact on rollback.
    }

    private function appendReportMenuToDefaultSettings(): void
    {
        $now = now();
        $menuSetting = DB::table('settings')
            ->where('key', 'default_pamong_menu_permissions')
            ->first();

        $menus = $this->decodeJsonArray($menuSetting?->value)
            ?: ['dashboard', 'materi', 'calendar', 'manual_attendance'];

        if (! in_array(self::REPORT_MENU, $menus, true)) {
            $menus[] = self::REPORT_MENU;
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_pamong_menu_permissions'],
            [
                'value' => json_encode(array_values(array_unique($menus))),
                'type' => 'json',
                'group' => 'permissions',
                'updated_at' => $now,
                'created_at' => $menuSetting?->created_at ?? $now,
            ]
        );

        $crudSetting = DB::table('settings')
            ->where('key', 'default_pamong_crud_permissions')
            ->first();

        $crud = $this->decodeJsonArray($crudSetting?->value)
            ?: [
                'materi' => ['view'],
                'calendar' => ['view'],
                'manual_attendance' => ['view', 'create'],
            ];

        $crud[self::REPORT_MENU] = $crud[self::REPORT_MENU] ?? self::DEFAULT_REPORT_CRUD;

        DB::table('settings')->updateOrInsert(
            ['key' => 'default_pamong_crud_permissions'],
            [
                'value' => json_encode($crud),
                'type' => 'json',
                'group' => 'permissions',
                'updated_at' => $now,
                'created_at' => $crudSetting?->created_at ?? $now,
            ]
        );
    }

    private function appendReportMenuToLegacyDefaultPermissionRows(): void
    {
        DB::table('pamong_permissions')
            ->where('is_excluded', false)
            ->orderBy('id')
            ->chunkById(100, function ($permissions) {
                foreach ($permissions as $permission) {
                    $menus = $this->decodeJsonArray($permission->menu_permissions);

                    if (! $this->isLegacyDefaultMenuSet($menus)) {
                        continue;
                    }

                    $menus[] = self::REPORT_MENU;
                    $crud = $this->decodeJsonArray($permission->crud_permissions) ?: [];
                    $crud[self::REPORT_MENU] = $crud[self::REPORT_MENU] ?? self::DEFAULT_REPORT_CRUD;

                    DB::table('pamong_permissions')
                        ->where('id', $permission->id)
                        ->update([
                            'menu_permissions' => json_encode(array_values(array_unique($menus))),
                            'crud_permissions' => json_encode($crud),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function isLegacyDefaultMenuSet(?array $menus): bool
    {
        if (! is_array($menus) || in_array(self::REPORT_MENU, $menus, true)) {
            return false;
        }

        sort($menus);

        foreach (self::LEGACY_DEFAULT_MENU_SETS as $legacySet) {
            sort($legacySet);

            if ($menus === $legacySet) {
                return true;
            }
        }

        return false;
    }

    private function decodeJsonArray($value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
};
