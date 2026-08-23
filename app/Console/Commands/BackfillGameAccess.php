<?php

namespace App\Console\Commands;

use App\Models\PamongPermission;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Pastikan semua pamong & pengurus PKG bisa melihat & memainkan game
 * (menu 'game' + 'gamification'), termasuk akun lama & pengaturan default.
 * Idempoten — aman dijalankan berkali-kali.
 */
class BackfillGameAccess extends Command
{
    protected $signature = 'pkg:backfill-game-access {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Berikan akses menu Game & Gamifikasi (view) ke semua akun tim + pengaturan default';

    /** Menu yang wajib bisa diakses semua tim untuk main game. */
    private const GAME_MENUS = ['gamification', 'game'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // 1) Update pengaturan DEFAULT untuk akun baru (bila tersimpan tanpa game).
        $this->syncDefaultSetting($dry);

        // 2) Backfill akun operasional non-excluded yang belum punya menu game.
        $updated = 0;
        $checked = 0;

        PamongPermission::query()
            ->where('is_excluded', false)
            ->chunkById(100, function ($permissions) use (&$updated, &$checked, $dry) {
                foreach ($permissions as $permission) {
                    $checked++;
                    $menus = is_array($permission->menu_permissions) ? $permission->menu_permissions : [];
                    $crud = is_array($permission->crud_permissions) ? $permission->crud_permissions : [];

                    $changed = false;

                    foreach (self::GAME_MENUS as $menu) {
                        if (! in_array($menu, $menus, true)) {
                            $menus[] = $menu;
                            $changed = true;
                        }
                        if (empty($crud[$menu]) || ! in_array('view', (array) $crud[$menu], true)) {
                            $crud[$menu] = array_values(array_unique(array_merge($crud[$menu] ?? [], ['view'])));
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $updated++;
                        if (! $dry) {
                            $permission->update([
                                'menu_permissions' => array_values(array_unique($menus)),
                                'crud_permissions' => $crud,
                            ]);
                        }
                    }
                }
            });

        $this->info(($dry ? '[DRY-RUN] ' : '') . "Akun diperiksa: {$checked}, diberi akses game: {$updated}.");

        if (! $dry) {
            Cache::forget('default_pamong_permissions');
            Cache::forget('default_pamong_permissions_model');
        }

        return self::SUCCESS;
    }

    private function syncDefaultSetting(bool $dry): void
    {
        $raw = Setting::get('default_pamong_menu_permissions', null, 'permissions');
        if (! $raw) {
            // Belum ada setting tersimpan → fallback const sudah memuat game, tak perlu diubah.
            $this->line('Pengaturan default belum tersimpan; memakai fallback (sudah memuat game).');
            return;
        }

        $menus = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($menus)) {
            return;
        }

        $before = $menus;
        foreach (self::GAME_MENUS as $menu) {
            if (! in_array($menu, $menus, true)) {
                $menus[] = $menu;
            }
        }

        if ($menus === $before) {
            $this->line('Pengaturan default sudah memuat menu game.');
            return;
        }

        $this->line(($dry ? '[DRY-RUN] ' : '') . 'Menambahkan game/gamification ke pengaturan default akun baru.');

        if (! $dry) {
            Setting::set('default_pamong_menu_permissions', json_encode(array_values(array_unique($menus))), 'permissions');

            // Pastikan crud default juga punya view untuk game.
            $crudRaw = Setting::get('default_pamong_crud_permissions', null, 'permissions');
            $crud = is_string($crudRaw) ? json_decode($crudRaw, true) : $crudRaw;
            $crud = is_array($crud) ? $crud : [];
            foreach (self::GAME_MENUS as $menu) {
                if (empty($crud[$menu]) || ! in_array('view', (array) $crud[$menu], true)) {
                    $crud[$menu] = array_values(array_unique(array_merge($crud[$menu] ?? [], ['view'])));
                }
            }
            Setting::set('default_pamong_crud_permissions', json_encode($crud), 'permissions');
        }
    }
}
