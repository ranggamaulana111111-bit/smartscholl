<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Baris "Super Admin" sengaja tidak pernah ditampilkan maupun ditulis:
     * peran itu selalu memegang seluruh izin (User::hasPermission), sehingga
     * matriks tidak mungkin mengunci administrator platform keluar dari sistem.
     */
    private const EDITABLE_ROLES = ['admin_sekolah', 'guru', 'siswa', 'orang_tua'];

    public function index(): View
    {
        $grants = [];

        foreach (RolePermission::query()
            ->whereIn('role', self::EDITABLE_ROLES)
            ->get(['role', 'permission_id']) as $row) {
            $grants[$row->role][$row->permission_id] = true;
        }

        $ids = Permission::query()->pluck('id', 'name');

        $groups = [];

        foreach (permissionGroups() as $groupKey => $group) {
            $rows = [];

            foreach ($group['permissions'] as $permission) {
                $id = $ids[$permission['name']] ?? null;

                $granted = [];

                foreach (self::EDITABLE_ROLES as $role) {
                    $granted[$role] = $id !== null && isset($grants[$role][$id]);
                }

                $rows[] = [
                    'name' => $permission['name'],
                    'label' => $permission['label'],
                    'defaults' => array_values(array_diff($permission['roles'], ['super_admin'])),
                    'granted' => $granted,
                ];
            }

            $groups[$groupKey] = [
                'label' => $group['label'],
                'description' => $group['description'],
                'permissions' => $rows,
            ];
        }

        $roles = array_intersect_key(appRoles(), array_flip(self::EDITABLE_ROLES));

        return view('permissions.index', [
            'groups' => $groups,
            'roles' => $roles,
            'editable' => auth()->user()->hasPermission('permissions.manage'),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request): RedirectResponse
    {
        $granted = $request->grants();
        $permissionIds = Permission::query()->pluck('id', 'name');

        // Snapshot kondisi lama dari database, bukan mengulang payload baru,
        // supaya diff audit benar-benar menunjukkan apa yang berubah.
        $idToName = $permissionIds->flip();
        $before = [];

        foreach (RolePermission::query()
            ->whereIn('role', self::EDITABLE_ROLES)
            ->get(['role', 'permission_id']) as $row) {
            $before[$row->role][] = $idToName->get($row->permission_id, (string) $row->permission_id);
        }

        $rows = [];

        foreach ($granted as $role => $names) {
            foreach ($names as $name) {
                $rows[] = [
                    'role' => $role,
                    'permission_id' => $permissionIds[$name],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($rows): void {
            DB::table('role_permissions')->whereIn('role', self::EDITABLE_ROLES)->delete();

            if ($rows !== []) {
                DB::table('role_permissions')->insert($rows);
            }
        });

        log_audit(
            'update_role_permissions',
            null,
            ['grants' => $before],
            ['grants' => $granted],
            null,
            'RolePermission'
        );

        return to_route('permissions.index')->with(
            'success',
            'Matriks hak akses berhasil disimpan. '.count($rows).' kombinasi peran dan izin aktif.'
        );
    }
}
