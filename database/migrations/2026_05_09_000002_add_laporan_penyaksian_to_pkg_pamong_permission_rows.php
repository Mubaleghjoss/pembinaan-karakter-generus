<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPORT_MENU = 'laporan_penyaksian';

    private const PKG_MENU_KEYS = ['pr', 'tracer_karakter', 'tugas_pkg'];

    private const DEFAULT_REPORT_CRUD = ['view', 'tindak_lanjut'];

    public function up(): void
    {
        DB::table('pamong_permissions')
            ->where('is_excluded', false)
            ->orderBy('id')
            ->chunkById(100, function ($permissions) {
                foreach ($permissions as $permission) {
                    $menus = $this->decodeJsonArray($permission->menu_permissions);

                    if (! $this->hasPkgMenuWithoutReportMenu($menus)) {
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

        Cache::forget('default_pamong_permissions');
        Cache::forget('default_pamong_permissions_model');
    }

    public function down(): void
    {
        // Data migration only. Keep existing permission choices intact on rollback.
    }

    private function hasPkgMenuWithoutReportMenu(?array $menus): bool
    {
        if (! is_array($menus) || in_array(self::REPORT_MENU, $menus, true)) {
            return false;
        }

        return count(array_intersect($menus, self::PKG_MENU_KEYS)) > 0;
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
