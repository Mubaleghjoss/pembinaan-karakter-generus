<?php
/**
 * Terapkan izin eksplisit ke akun operasional (teacher / pkg_manager).
 *
 * Pemakaian:
 *   php scripts/terapkan-izin-operasional.php            -> DRY RUN (tidak mengubah data)
 *   php scripts/terapkan-izin-operasional.php --apply    -> terapkan perubahan
 *
 * Yang dilakukan saat --apply:
 *   1. Matikan is_excluded (bypass izin) pada akun operasional.
 *   2. Terapkan paket izin sesuai peran:
 *        teacher      -> paket "Pamong Pembimbing"  (boleh presensi manual semua generus)
 *        pkg_manager  -> paket "Pengurus Verifikator" (tanpa presensi manual)
 *   3. Beri peran tugas (badge) awal: teacher -> pengisi_presensi + verifikator_tugas
 */
$base = dirname(__DIR__);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PamongPermission;
use App\Models\User;
use App\Support\DutyRole;
use App\Support\OperationalPermissionPreset;

$apply = in_array('--apply', $argv, true);

$presetForRole = [
    'teacher' => 'pamong_pembimbing',
    'pkg_manager' => 'pengurus_verifikator',
];
$dutyForRole = [
    'teacher' => ['pengisi_presensi', 'verifikator_tugas'],
    'pkg_manager' => ['verifikator_tugas'],
];

$users = User::with(['role', 'pamongPermission'])
    ->whereHas('role', fn ($q) => $q->whereIn('name', User::operationalRoleNames()))
    ->orderBy('username')
    ->get();

echo ($apply ? "=== MODE APPLY (data diubah) ===" : "=== DRY RUN (tidak ada perubahan) ===")."\n";
echo "Akun operasional: ".$users->count()."\n\n";

$stats = ['excluded_dimatikan' => 0, 'paket_diterapkan' => 0, 'duty_diisi' => 0, 'dilewati' => 0];

foreach ($users as $u) {
    $roleName = $u->role->name ?? '-';
    $presetKey = $presetForRole[$roleName] ?? null;

    if (! $presetKey) {
        echo sprintf("  - %-22s peran %s tidak dikenal -> DILEWATI\n", $u->username, $roleName);
        $stats['dilewati']++;
        continue;
    }

    $preset = OperationalPermissionPreset::permissionsFor($presetKey);
    $perm = $u->pamongPermission;
    $wasExcluded = (bool) ($perm->is_excluded ?? false);
    $menuBefore = count($perm->menu_permissions ?? []);

    // Hitung kemampuan presensi manual SEBELUM dan SESUDAH.
    $manualBefore = $u->hasPamongMenuAccess('manual_attendance')
        && $u->hasPamongCrudPermission('manual_attendance', 'create');
    $manualAfter = in_array('manual_attendance', $preset['menu_permissions'], true)
        && in_array('create', $preset['crud_permissions']['manual_attendance'] ?? [], true);

    $dutyBefore = (array) ($u->duty_roles ?? []);
    $dutyAfter = DutyRole::sanitize($dutyForRole[$roleName] ?? []);

    printf(
        "  - %-22s %-14s excluded:%-3s menu:%2d -> %2d | presensi manual:%s -> %s | badge:%d -> %d\n",
        $u->username,
        $roleName,
        $wasExcluded ? 'ya' : '-',
        $menuBefore,
        count($preset['menu_permissions']),
        $manualBefore ? 'ya' : 'no',
        $manualAfter ? 'ya' : 'no',
        count($dutyBefore),
        count($dutyAfter)
    );

    if (! $apply) {
        continue;
    }

    PamongPermission::updateOrCreate(
        ['user_id' => $u->id],
        [
            'menu_permissions' => $preset['menu_permissions'],
            'crud_permissions' => $preset['crud_permissions'],
            'is_excluded' => false,
        ]
    );
    if ($wasExcluded) {
        $stats['excluded_dimatikan']++;
    }
    $stats['paket_diterapkan']++;

    if (empty($dutyBefore) && ! empty($dutyAfter)) {
        $u->duty_roles = $dutyAfter;
        $u->save();
        $stats['duty_diisi']++;
    }
}

echo "\n";
if ($apply) {
    echo "SELESAI: paket diterapkan={$stats['paket_diterapkan']}, bypass dimatikan={$stats['excluded_dimatikan']}, badge diisi={$stats['duty_diisi']}, dilewati={$stats['dilewati']}\n";
    Cache::flush();
    echo "Cache dibersihkan.\n";
} else {
    echo "Tidak ada data yang diubah. Jalankan ulang dengan --apply untuk menerapkan.\n";
}
