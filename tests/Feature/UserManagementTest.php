<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $sekolahA;

    private Tenant $sekolahB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->sekolahA = Tenant::factory()->create(['name' => 'SD Negeri A']);
        $this->sekolahB = Tenant::factory()->create(['name' => 'SD Negeri B']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@sekolah.test',
            'role' => 'guru',
            'tenant_id' => $this->sekolahA->id,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    public function test_super_admin_lists_users_across_tenants(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->guru($this->sekolahA->id)->create(['name' => 'Guru A']);
        User::factory()->guru($this->sekolahB->id)->create(['name' => 'Guru B']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Guru A')
            ->assertSee('Guru B');
    }

    public function test_super_admin_creates_user(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload())
            ->assertRedirect(route('users.index'));

        $created = User::withoutGlobalScopes()->where('email', 'budi@sekolah.test')->firstOrFail();

        $this->assertSame('guru', $created->role);
        $this->assertSame($this->sekolahA->id, $created->tenant_id);
        $this->assertTrue(Hash::check('rahasia123', $created->password));
    }

    public function test_password_confirmation_must_match(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['password_confirmation' => 'lain12345']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'budi@sekolah.test']);
    }

    public function test_role_must_be_known(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['role' => 'direktur']))
            ->assertSessionHasErrors('role');
    }

    public function test_non_platform_roles_require_a_tenant(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['tenant_id' => '']))
            ->assertSessionHasErrors('tenant_id');
    }

    public function test_super_admin_may_exist_without_a_tenant(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload([
                'role' => 'super_admin',
                'tenant_id' => '',
                'email' => 'admin2@sekolah.test',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'admin2@sekolah.test',
            'role' => 'super_admin',
            'tenant_id' => null,
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->guru($this->sekolahA->id)->create(['email' => 'budi@sekolah.test']);

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload())
            ->assertSessionHasErrors('email');
    }

    public function test_email_is_normalised_to_lowercase(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['email' => '  Budi@Sekolah.TEST ']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'budi@sekolah.test']);
    }

    public function test_admin_sekolah_places_new_user_in_own_tenant(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['tenant_id' => $this->sekolahB->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'budi@sekolah.test',
            'tenant_id' => $this->sekolahA->id,
        ]);
    }

    public function test_admin_sekolah_cannot_assign_admin_role(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['role' => 'admin_sekolah']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'budi@sekolah.test']);
    }

    public function test_admin_sekolah_cannot_assign_super_admin_role(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create();

        $this->actingAs($admin)
            ->post(route('users.store'), $this->payload(['role' => 'super_admin']))
            ->assertSessionHasErrors('role');
    }

    public function test_admin_sekolah_only_sees_own_tenant(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create();
        User::factory()->guru($this->sekolahB->id)->create(['name' => 'Guru Sekolah B']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Guru Sekolah B');
    }

    public function test_admin_sekolah_cannot_open_user_from_another_tenant(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create();
        $target = User::factory()->guru($this->sekolahB->id)->create();

        $this->actingAs($admin)
            ->get(route('users.edit', $target))
            ->assertNotFound();
    }

    public function test_admin_sekolah_cannot_edit_another_admin(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create();
        $other = User::factory()->adminSekolah($this->sekolahA->id)->create();

        $this->actingAs($admin)
            ->put(route('users.update', $other), $this->payload([
                'name' => 'Diretas',
                'email' => 'other@sekolah.test',
            ]))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $other->id, 'name' => $other->name]);
    }

    public function test_admin_sekolah_can_edit_self_but_not_own_role(): void
    {
        $admin = User::factory()->adminSekolah($this->sekolahA->id)->create(['name' => 'Awal']);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => 'Nama Baru',
                'email' => $admin->email,
                'role' => 'guru',
                'password' => '',
            ])
            ->assertSessionHasNoErrors();

        $admin->refresh();

        $this->assertSame('Nama Baru', $admin->name);
        $this->assertSame('admin_sekolah', $admin->role, 'Admin Sekolah tidak boleh menurunkan perannya sendiri lewat form.');
    }

    public function test_guru_and_siswa_cannot_open_user_management(): void
    {
        $guru = User::factory()->guru($this->sekolahA->id)->create();
        $siswa = User::factory()->siswa($this->sekolahA->id)->create();

        $this->actingAs($guru)->get(route('users.index'))->assertForbidden();
        $this->actingAs($siswa)->get(route('users.create'))->assertForbidden();
        $this->actingAs($siswa)->get(route('permissions.index'))->assertForbidden();
    }

    public function test_update_without_password_keeps_the_old_one(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->guru($this->sekolahA->id)->create();
        $original = $target->password;

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'name' => 'Nama Baru',
                'email' => $target->email,
                'role' => 'guru',
                'tenant_id' => $this->sekolahA->id,
                'password' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($original, $target->refresh()->password);
    }

    public function test_update_can_replace_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->guru($this->sekolahA->id)->create();

        $this->actingAs($admin)
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'guru',
                'tenant_id' => $this->sekolahA->id,
                'password' => 'katasandi456',
                'password_confirmation' => 'katasandi456',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('katasandi456', $target->refresh()->password));
    }

    public function test_cannot_delete_own_account(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_delete_the_last_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_super_admin_can_delete_another_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $other))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_user_with_schedules_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->guru($this->sekolahA->id)->create();
        $subject = Subject::factory()->create(['tenant_id' => $this->sekolahA->id]);

        Schedule::factory()->create([
            'tenant_id' => $this->sekolahA->id,
            'user_id' => $target->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $target))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_deletion_removes_permission_overrides(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->guru($this->sekolahA->id)->create();

        $this->actingAs($admin)->put(route('users.permissions.update', $target), [
            'overrides' => [
                ['permission' => 'journals.manage', 'effect' => 'deny'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('user_permissions', ['user_id' => $target->id, 'effect' => 'deny']);

        $this->actingAs($admin)->delete(route('users.destroy', $target));

        $this->assertDatabaseMissing('user_permissions', ['user_id' => $target->id]);
    }

    public function test_creating_user_writes_an_audit_entry(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('users.store'), $this->payload());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'create',
            'entity_type' => 'User',
        ]);
    }

    public function test_audit_entry_never_stores_the_password_hash(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('users.store'), $this->payload());

        $log = AuditLog::withoutGlobalScopes()->where('action', 'create')->latest('id')->firstOrFail();

        $this->assertStringNotContainsString(Hash::info('rounds')['algoName'], json_encode($log->new_values));
        $this->assertArrayNotHasKey('password', $log->new_values);
    }

    public function test_index_can_filter_by_role_and_search(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->guru($this->sekolahA->id)->create(['name' => 'Guru Alpha']);
        User::factory()->siswa($this->sekolahA->id)->create(['name' => 'Siswa Beta']);

        $this->actingAs($admin)
            ->get(route('users.index', ['role' => 'guru']))
            ->assertOk()
            ->assertSee('Guru Alpha')
            ->assertDontSee('Siswa Beta');

        $this->actingAs($admin)
            ->get(route('users.index', ['q' => 'Beta']))
            ->assertOk()
            ->assertSee('Siswa Beta')
            ->assertDontSee('Guru Alpha');
    }

    public function test_the_last_super_admin_cannot_be_demoted(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->put(route('users.update', $super), [
                'name' => $super->name,
                'email' => $super->email,
                'role' => 'guru',
                'tenant_id' => $this->sekolahA->id,
            ])
            ->assertSessionHasErrors();

        $this->assertSame('super_admin', $super->fresh()->role);
    }

    public function test_a_super_admin_may_be_demoted_when_another_one_remains(): void
    {
        $super = User::factory()->superAdmin()->create();
        User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->put(route('users.update', $super), [
                'name' => $super->name,
                'email' => $super->email,
                'role' => 'guru',
                'tenant_id' => $this->sekolahA->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('guru', $super->fresh()->role);
    }
}
