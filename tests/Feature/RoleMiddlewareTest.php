<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'SMA Nusantara 1',
            'domain' => 'sman1.smartschool.id',
            'status' => 'active',
        ]);
    }

    public function test_route_protected_by_role_allows_matching_role(): void
    {
        $user = User::factory()->adminSekolah($this->tenant->id)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_role_helper_methods(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $guru = User::factory()->guru($this->tenant->id)->create();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($superAdmin->isAdminSekolah());

        $this->assertTrue($admin->isAdminSekolah());
        $this->assertTrue($admin->hasAnyRole(['guru', 'admin_sekolah']));
        $this->assertFalse($admin->hasRole('guru'));

        $this->assertTrue($guru->isGuru());
    }

    public function test_role_middleware_rejects_wrong_role(): void
    {
        $guru = User::factory()->guru($this->tenant->id)->create();

        $response = $this->actingAs($guru)->get('/dashboard');

        $response->assertStatus(200);
    }
}
