<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Menyelaraskan tabel permissions dan role_permissions dengan config/permissions.php.
     *
     * Izin yang tak lagi ada di config dihapus (pivot ikut terhapus karena cascade),
     * sedangkan hak akses yang sudah disesuaikan Super Admin tidak ditimpa ulang.
     */
    public function run(): void
    {
        $catalogue = permissionCatalogue();

        $this->command?->info('Seeding '.count($catalogue).' permissions...');

        foreach ($catalogue as $name => $permission) {
            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $permission['label'],
                    'group_key' => $permission['group'],
                ],
            );
        }

        Permission::query()->whereNotIn('name', array_keys($catalogue))->delete();

        $permissionIds = Permission::query()
            ->whereIn('name', array_keys($catalogue))
            ->pluck('id', 'name');

        $now = now();
        $grants = [];

        foreach (array_keys(appRoles()) as $role) {
            foreach (permissionDefaultsForRole($role) as $name) {
                $grants[] = [
                    'role' => $role,
                    'permission_id' => $permissionIds[$name],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($grants, 200) as $chunk) {
            DB::table('role_permissions')->insertOrIgnore($chunk);
        }

        $this->command?->info('Total '.count($grants).' role permissions tersinkron.');
    }
}
