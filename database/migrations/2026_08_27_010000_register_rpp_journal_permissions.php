<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preserve the access that operational roles had before Jurnal RPP became
     * an explicit configurable permission.
     */
    public function up(): void
    {
        DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->leftJoin('pamong_permissions', 'pamong_permissions.user_id', '=', 'users.id')
            ->whereIn('roles.name', User::operationalRoleNames())
            ->select([
                'users.id as user_id',
                'pamong_permissions.id as permission_id',
                'pamong_permissions.menu_permissions',
                'pamong_permissions.crud_permissions',
                'roles.name as role_name',
            ])
            ->orderBy('users.id')
            ->each(function (object $account): void {
                $menus = $this->decodeArray($account->menu_permissions);
                $crud = $this->decodeArray($account->crud_permissions);

                if ($account->permission_id === null) {
                    $menus = ['dashboard', 'presensi'];
                    $crud = ['presensi' => ['view']];
                }

                if (! in_array('rpp_journals', $menus, true)) {
                    $menus[] = 'rpp_journals';
                }

                $crud['rpp_journals'] = $account->role_name === User::ROLE_PKG_MANAGER
                    ? ['view', 'manage']
                    : ['view'];

                $values = [
                    'menu_permissions' => json_encode(array_values($menus), JSON_UNESCAPED_UNICODE),
                    'crud_permissions' => json_encode($crud, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ];

                if ($account->permission_id === null) {
                    DB::table('pamong_permissions')->insert($values + [
                        'user_id' => $account->user_id,
                        'is_excluded' => false,
                        'created_at' => now(),
                    ]);
                } else {
                    DB::table('pamong_permissions')
                        ->where('id', $account->permission_id)
                        ->update($values);
                }
            });
    }

    public function down(): void
    {
        DB::table('pamong_permissions')
            ->select(['id', 'menu_permissions', 'crud_permissions'])
            ->orderBy('id')
            ->each(function (object $permission): void {
                $menus = array_values(array_filter(
                    $this->decodeArray($permission->menu_permissions),
                    static fn (mixed $menu): bool => $menu !== 'rpp_journals'
                ));
                $crud = $this->decodeArray($permission->crud_permissions);
                unset($crud['rpp_journals']);

                DB::table('pamong_permissions')
                    ->where('id', $permission->id)
                    ->update([
                        'menu_permissions' => json_encode($menus, JSON_UNESCAPED_UNICODE),
                        'crud_permissions' => json_encode($crud, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            });
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }
};
