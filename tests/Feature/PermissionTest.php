<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private const EDITABLE_ROLES = ['admin_sekolah', 'guru', 'orang_tua', 'siswa'];

    /**
     * Pasangan rute -> izin yang harus mencerminkannya. Rute yang juga
     * memakai middleware `permission:` dikecualikan karena akses efektifnya
     * sudah ditentukan oleh izin, bukan oleh role.
     */
    private const ROUTE_PERMISSION_MAP = [
        'dashboard' => 'dashboard.view',
        'students.index' => 'students.manage',
        'students.progress' => 'students.view',
        'teachers.index' => 'teachers.manage',
        'rombels.index' => 'rombels.manage',
        'academic-years.index' => 'academic_years.manage',
        'subjects.index' => 'subjects.manage',
        'attendance.index' => 'attendance.view',
        'attendance.scan' => 'attendance.scan',
        'attendance.manual' => 'attendance.manual',
        'attendance.qrcodes' => 'attendance.qrcodes',
        'attendance.recap' => 'attendance.view',
        'attendance.scan-events' => 'attendance.audit',
        'attendance.schedule.scan.mark' => 'attendance.manual',
        'attendance.schedule.scan.remaining' => 'attendance.manual',
        'schedules.index' => 'schedules.view',
        'schedules.create' => 'schedules.manage',
        'journals.index' => 'journals.view',
        'journals.create' => 'journals.manage',
        'assignments.index' => 'assignments.view',
        'assignments.create' => 'assignments.manage',
        'assignments.submit' => 'assignments.submit',
        'assessments.index' => 'assessments.view',
        'assessments.create' => 'assessments.manage',
        'parent.dashboard' => 'parent.dashboard',
        'ews.index' => 'ews.view',
        'ews.runCheck' => 'ews.manage',
        'audit-logs.index' => 'audit.view',
        'settings.index' => 'settings.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function rolesOnRoute(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route, "Rute {$routeName} tidak ditemukan.");

        $gates = collect($route->gatherMiddleware())
            ->filter(fn ($middleware): bool => is_string($middleware) && str_starts_with($middleware, 'role:'))
            ->flatMap(fn (string $middleware): array => explode(',', substr($middleware, 5)))
            ->all();

        $roles = $gates === [] ? ['super_admin', ...self::EDITABLE_ROLES] : $gates;

        $roles = array_values(array_diff($roles, ['super_admin']));
        sort($roles);

        return $roles;
    }

    public function test_permission_defaults_mirror_the_route_role_gates(): void
    {
        $catalogue = permissionCatalogue();

        foreach (self::ROUTE_PERMISSION_MAP as $routeName => $permissionName) {
            $this->assertArrayHasKey($permissionName, $catalogue, "Izin {$permissionName} belum ada di config.");

            $expected = $this->rolesOnRoute($routeName);
            $actual = array_values($catalogue[$permissionName]['roles']);
            sort($actual);

            $this->assertSame(
                $expected,
                $actual,
                "Izin {$permissionName} tidak lagi cocok dengan gate role pada rute {$routeName}."
            );
        }
    }

    public function test_every_catalogue_permission_exists_in_the_database(): void
    {
        $this->assertSame(
            count(permissionNames()),
            Permission::count(),
            'Jalankan PermissionSeeder agar tabel permissions sinkron dengan config.'
        );
    }

    public function test_super_admin_always_holds_every_permission(): void
    {
        $admin = User::factory()->superAdmin()->create();

        foreach (permissionNames() as $name) {
            $this->assertTrue($admin->hasPermission($name), "Super Admin kehilangan izin {$name}.");
        }
    }

    public function test_super_admin_is_not_locked_out_when_the_matrix_is_emptied(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('permissions.update'), ['grants' => []])
            ->assertRedirect(route('permissions.index'));

        $this->assertSame(0, DB::table('role_permissions')->count());

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('permissions.index'))->assertOk();

        foreach (permissionNames() as $name) {
            $this->assertTrue($admin->hasPermission($name));
        }
    }

    public function test_seeded_defaults_match_config(): void
    {
        $granted = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->get(['role', 'name']);

        foreach (array_keys(appRoles()) as $role) {
            $expected = permissionDefaultsForRole($role);
            sort($expected);

            $actual = $granted->where('role', $role)->pluck('name')->all();
            sort($actual);

            $this->assertSame($expected, $actual, "Hak akses bawaan peran {$role} tidak sesuai config.");
        }
    }

    public function test_admin_sekolah_can_read_the_matrix_but_not_save_it(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->adminSekolah($tenant->id)->create();

        $this->actingAs($admin)
            ->get(route('permissions.index'))
            ->assertOk()
            ->assertSee('Matriks Hak Akses');

        $this->actingAs($admin)
            ->put(route('permissions.update'), ['grants' => ['guru' => ['students.manage']]])
            ->assertForbidden();

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'guru',
            'permission_id' => Permission::where('name', 'students.manage')->value('id'),
        ]);
    }

    public function test_super_admin_can_revoke_a_role_permission(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $grants = [];
        foreach (self::EDITABLE_ROLES as $role) {
            $grants[$role] = permissionDefaultsForRole($role);
        }
        $grants['guru'] = array_values(array_diff($grants['guru'], ['journals.manage']));

        $this->actingAs($admin)
            ->put(route('permissions.update'), ['grants' => $grants])
            ->assertRedirect(route('permissions.index'));

        $guru = User::factory()->guru(Tenant::factory()->create()->id)->create();

        $this->assertFalse($guru->fresh()->hasPermission('journals.manage'));
        $this->assertTrue($guru->fresh()->hasPermission('attendance.scan'));
    }

    public function test_saving_the_matrix_discards_unknown_and_platform_entries(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('permissions.update'), [
                'grants' => [
                    'super_admin' => ['students.manage'],
                    'direktur' => ['students.manage'],
                    'guru' => ['journals.manage', 'izin.antikapitalisme', 'journals.manage'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $rows = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->get(['role', 'name']);

        $this->assertSame(
            [['guru', 'journals.manage']],
            $rows->map(fn ($row): array => [$row->role, $row->name])->all()
        );
    }

    public function test_user_override_grants_a_missing_permission(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->assertFalse($guru->hasPermission('settings.system'));

        $this->actingAs($admin)->put(route('users.permissions.update', $guru), [
            'overrides' => [['permission' => 'settings.system', 'effect' => 'grant']],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($guru->fresh()->hasPermission('settings.system'));
    }

    public function test_user_override_deny_beats_the_role_grant(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->assertTrue($guru->hasPermission('journals.manage'));

        $this->actingAs($admin)->put(route('users.permissions.update', $guru), [
            'overrides' => [['permission' => 'journals.manage', 'effect' => 'deny']],
        ])->assertSessionHasNoErrors();

        $this->assertFalse($guru->fresh()->hasPermission('journals.manage'));
    }

    public function test_inherit_removes_a_previous_override(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->actingAs($admin)->put(route('users.permissions.update', $guru), [
            'overrides' => [['permission' => 'journals.manage', 'effect' => 'deny']],
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('users.permissions.update', $guru), [
            'overrides' => [
                ['permission' => 'journals.manage', 'effect' => 'inherit'],
                ['permission' => 'attendance.scan', 'effect' => 'inherit'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $guru->id]);
        $this->assertTrue($guru->fresh()->hasPermission('journals.manage'));
    }

    public function test_unknown_permission_is_rejected(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->actingAs($admin)->put(route('users.permissions.update', $guru), [
            'overrides' => [['permission' => 'izin.palsu', 'effect' => 'grant']],
        ])->assertSessionHasErrors('overrides.0.permission');
    }

    public function test_only_super_admin_may_edit_user_overrides(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->adminSekolah($tenant->id)->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->actingAs($admin)->put(route('users.permissions.update', $guru), [
            'overrides' => [['permission' => 'settings.system', 'effect' => 'grant']],
        ])->assertForbidden();

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $guru->id]);
    }

    public function test_rendered_matrix_matches_the_stored_grants_per_role(): void
    {
        $journalsManage = Permission::where('name', 'journals.manage')->value('id');
        $rombelsManage = Permission::where('name', 'rombels.manage')->value('id');

        DB::table('role_permissions')->delete();
        DB::table('role_permissions')->insert([
            [
                'role' => 'guru',
                'permission_id' => $journalsManage,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role' => 'admin_sekolah',
                'permission_id' => $rombelsManage,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('permissions.index'))->assertOk();

        $rows = collect($response->viewData('groups'))
            ->flatMap(fn (array $group): array => $group['permissions'])
            ->keyBy('name');

        $this->assertTrue($rows['journals.manage']['granted']['guru'], 'Guru memegang journals.manage.');
        $this->assertFalse($rows['journals.manage']['granted']['siswa'], 'Siswa tidak memegang journals.manage.');
        $this->assertFalse($rows['rombels.manage']['granted']['guru'], 'Guru tidak memegang rombels.manage.');
        $this->assertTrue($rows['rombels.manage']['granted']['admin_sekolah'], 'Admin Sekolah memegang rombels.manage.');
        $this->assertFalse($rows['rombels.manage']['granted']['orang_tua']);
    }

    public function test_matrix_renders_one_checkbox_per_role_and_permission(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $content = $this->actingAs($admin)->get(route('permissions.index'))->assertOk()->getContent();

        foreach (permissionNames() as $name) {
            foreach (['admin_sekolah', 'guru', 'siswa', 'orang_tua'] as $role) {
                $this->assertMatchesRegularExpression(
                    '/name="grants\['.$role.'\]\[\]"\s+value="'.preg_quote($name, '/').'"/',
                    $content,
                    "Checkbox {$role} untuk izin {$name} tidak dirender."
                );
            }
        }
    }

    public function test_saving_the_matrix_preserves_per_role_distinctions(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $grants = [
            'guru' => ['journals.manage', 'attendance.scan'],
            'siswa' => [],
            'admin_sekolah' => ['students.manage'],
            'orang_tua' => [],
        ];

        $this->actingAs($admin)
            ->put(route('permissions.update'), ['grants' => $grants])
            ->assertSessionHasNoErrors();

        $rows = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->get(['role', 'name'])
            ->map(fn ($row): string => $row->role.':'.$row->name)
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'admin_sekolah:students.manage',
            'guru:attendance.scan',
            'guru:journals.manage',
        ], $rows);
    }

    public function test_super_admin_account_cannot_carry_overrides(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->put(route('users.permissions.update', $other), [
            'overrides' => [['permission' => 'students.manage', 'effect' => 'deny']],
        ])->assertSessionHasErrors();

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $other->id]);
        $this->assertTrue($other->fresh()->hasPermission('students.manage'));
    }

    public function test_unknown_permission_name_never_grants_access(): void
    {
        $guru = User::factory()->guru(Tenant::factory()->create()->id)->create();

        $this->assertFalse($guru->hasPermission('tidak.ada.izin.ini'));
        $this->assertFalse($guru->hasPermission(''));
    }

    public function test_revoking_users_manage_closes_the_whole_user_module(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->adminSekolah($tenant->id)->create();

        $this->assertTrue($admin->hasPermission('users.manage'));

        $this->revokeFromRole('users.manage', 'admin_sekolah');

        // Tiap request nyata memuat ulang User dari sesi, jadi cache izin per
        // instance harus ikut di-refresh agar pengujian setara dengan produksi.
        $admin = $admin->fresh();

        $this->assertFalse($admin->hasPermission('users.manage'));

        $this->actingAs($admin)->get(route('users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('users.store'))->assertForbidden();
    }

    public function test_revoking_permissions_view_closes_the_matrix(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->adminSekolah($tenant->id)->create();

        $this->revokeFromRole('permissions.view', 'admin_sekolah');

        $this->actingAs($admin)->get(route('permissions.index'))->assertForbidden();
    }

    public function test_super_admin_keeps_access_after_those_permissions_are_revoked(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->revokeFromRole('users.manage', 'admin_sekolah');
        $this->revokeFromRole('permissions.view', 'admin_sekolah');

        $this->actingAs($super)->get(route('users.index'))->assertOk();
        $this->actingAs($super)->get(route('permissions.index'))->assertOk();
    }

    public function test_admin_sekolah_never_sees_the_per_user_override_form(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->adminSekolah($tenant->id)->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->actingAs($admin)
            ->get(route('users.edit', $guru))
            ->assertOk()
            ->assertDontSee('Pengecualian Akses')
            ->assertDontSee(route('users.permissions.update', $guru));
    }

    public function test_admin_sekolah_cannot_write_per_user_overrides_even_with_users_manage(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->adminSekolah($tenant->id)->create();
        $guru = User::factory()->guru($tenant->id)->create();

        $this->assertTrue($admin->hasPermission('users.manage'));
        $this->assertFalse($admin->hasPermission('permissions.manage'));

        $this->actingAs($admin)
            ->put(route('users.permissions.update', $guru), [
                'overrides' => [['permission' => 'students.manage', 'effect' => 'deny']],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $guru->id]);
    }

    public function test_matrix_audit_records_the_state_before_the_change(): void
    {
        $super = User::factory()->superAdmin()->create();

        $grants = [];

        foreach (self::EDITABLE_ROLES as $role) {
            $grants[$role] = permissionDefaultsForRole($role);
        }

        $grants['guru'] = array_values(array_diff($grants['guru'], ['journals.manage']));

        $this->actingAs($super)
            ->put(route('permissions.update'), ['grants' => $grants])
            ->assertRedirect(route('permissions.index'));

        $log = AuditLog::query()->latest('id')->first();

        $this->assertSame('update_role_permissions', $log->action);
        $this->assertContains(
            'journals.manage',
            $log->old_values['grants']['guru'] ?? [],
            'Audit harus mencatat izin lama yang justru dicabut.'
        );
        $this->assertNotContains('journals.manage', $log->new_values['grants']['guru'] ?? []);
    }

    private function revokeFromRole(string $permission, string $role): void
    {
        $id = Permission::where('name', $permission)->value('id');

        DB::table('role_permissions')
            ->where('role', $role)
            ->where('permission_id', $id)
            ->delete();
    }

    /**
     * `->middleware('role:a,b,permission:x')` dipecah pada koma, sehingga
     * `permission:x` berakhir jadi parameter role, bukan middleware izin.
     * Rute yang ditulis seperti itu lolos dari kontrol akses secara diam-diam.
     */
    public function test_permission_middleware_is_never_swallowed_into_role_parameters(): void
    {
        $expected = [
            'users.index' => 'permission:users.manage',
            'users.create' => 'permission:users.manage',
            'users.store' => 'permission:users.manage',
            'users.edit' => 'permission:users.manage',
            'users.update' => 'permission:users.manage',
            'users.destroy' => 'permission:users.manage',
            'users.permissions.update' => 'permission:permissions.manage',
            'permissions.index' => 'permission:permissions.view',
            'permissions.update' => 'permission:permissions.manage',
        ];

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Rute {$routeName} tidak ditemukan.");

            $entries = array_map('strval', $route->gatherMiddleware());

            foreach ($entries as $entry) {
                if (! str_starts_with($entry, 'role:')) {
                    continue;
                }

                $this->assertStringNotContainsString(
                    'permission:',
                    $entry,
                    "Rute {$routeName} menyatukan permission: ke dalam parameter role:, sehingga middleware izin tidak pernah dijalankan."
                );
            }

            $this->assertContains(
                $permission,
                $entries,
                "Rute {$routeName} harus memakai middleware {$permission} secara terpisah."
            );
        }
    }
}
