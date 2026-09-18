<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'id' => '0192e001-0000-7000-8000-000000000001',
            'name' => 'SMA Nusantara 1',
            'domain' => 'sman1.smartschool.id',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'id' => '0192e001-0000-7000-8000-000000000002',
            'name' => 'SMP Harapan Bangsa',
            'domain' => 'smpharapan.smartschool.id',
            'status' => 'active',
        ]);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();
        $this->adminB = User::factory()->adminSekolah($this->tenantB->id)->create();
    }

    public function test_user_scope_limits_to_own_tenant(): void
    {
        $this->actingAs($this->adminA);

        $users = User::forCurrentTenant()->get();

        $this->assertTrue($users->contains('id', $this->adminA->id));
        $this->assertFalse($users->contains('id', $this->adminB->id));
    }

    public function test_user_autofills_tenant_id_on_create(): void
    {
        $this->actingAs($this->adminA);

        $newUser = User::create([
            'name' => 'Guru Baru',
            'email' => 'gurubaru@example.com',
            'password' => 'password',
            'role' => 'guru',
        ]);

        $this->assertEquals($this->tenantA->id, $newUser->tenant_id);
    }

    public function test_super_admin_sees_all_tenants(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $users = User::forCurrentTenant()->get();

        $this->assertTrue($users->contains('id', $this->adminA->id));
        $this->assertTrue($users->contains('id', $this->adminB->id));
    }

    public function test_user_global_scope_filters_by_tenant(): void
    {
        $this->actingAs($this->adminA);

        $users = User::all();

        $this->assertCount(1, $users);
        $this->assertEquals($this->adminA->id, $users->first()->id);
    }

    public function test_super_admin_global_scope_sees_everything(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $users = User::all();

        $this->assertCount(3, $users);
    }
}
