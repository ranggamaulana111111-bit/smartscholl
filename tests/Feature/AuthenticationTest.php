<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
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

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_user_can_authenticate(): void
    {
        $user = User::factory()->adminSekolah($this->tenant->id)->create([
            'email' => 'admin@sekolah.id',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@sekolah.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_authenticate_with_wrong_password(): void
    {
        User::factory()->adminSekolah($this->tenant->id)->create([
            'email' => 'admin@sekolah.id',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@sekolah.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_user_from_inactive_tenant_is_logged_out(): void
    {
        $inactiveTenant = Tenant::create([
            'name' => 'Sekolah Nonaktif',
            'domain' => 'nonaktif.smartschool.id',
            'status' => 'inactive',
        ]);

        $user = User::factory()->adminSekolah($inactiveTenant->id)->create([
            'email' => 'admin@nonaktif.id',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@nonaktif.id',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_accessible_when_authenticated(): void
    {
        $user = User::factory()->adminSekolah($this->tenant->id)->create();

        $this->actingAs($user)->get(route('dashboard'))->assertStatus(200);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->adminSekolah($this->tenant->id)->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
