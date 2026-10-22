<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPermission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentLoginAccountTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->tenantA = Tenant::factory()->create(['domain' => 'sman1.smartschool.id']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'smpharapan.smartschool.id']);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();
        $this->adminB = User::factory()->adminSekolah($this->tenantB->id)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'nisn' => '8677413804',
            'nis' => '01922944',
            'name' => 'Ghaffar Bintang',
            'gender' => 'L',
            'birth_date' => '2009-01-14',
            'birth_place' => 'Bandung',
            'address' => 'Jl. Cendana',
            'phone' => '081298765432',
        ], $overrides);
    }

    private function revokeUsersManage(User $user): void
    {
        UserPermission::create([
            'user_id' => $user->id,
            'permission_id' => Permission::where('name', 'users.manage')->value('id'),
            'effect' => 'deny',
        ]);
    }

    public function test_account_is_created_and_linked_when_student_is_added(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@SMA1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('students.index'));

        $student = Student::where('nisn', '8677413804')->sole();

        $this->assertNotNull($student->user_id, 'students.user_id harus tertaut ke akun yang baru dibuat.');
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'tenant_id' => $this->tenantA->id,
            'name' => 'Ghaffar Bintang',
            'email' => 'ghaffar@sma1.smartschool.id',
            'role' => 'siswa',
        ]);

        $account = $student->user()->sole();
        $this->assertTrue(Hash::check('rahasia-siswa', $account->password), 'Kata sandi harus tersimpan sebagai hash.');
    }

    public function test_account_stays_optional_when_adding_a_student(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload())
            ->assertSessionHasNoErrors();

        $student = Student::where('nisn', '8677413804')->sole();

        $this->assertNull($student->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'ghaffar@sma1.smartschool.id']);
    }

    public function test_existing_student_can_be_given_an_account_from_the_edit_form(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
            'name' => 'Ghaffar Bintang',
        ]);

        $this->assertNull($student->user_id, 'Fikstur siswa harus mulai tanpa akun.');

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
            ]))
            ->assertSessionHasNoErrors();

        $student->refresh();

        $this->assertNotNull($student->user_id);
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'tenant_id' => $this->tenantA->id,
            'email' => 'ghaffar@sma1.smartschool.id',
            'role' => 'siswa',
        ]);
    }

    public function test_linked_account_email_can_be_changed_without_touching_the_password(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
        $account = User::factory()->siswa($this->tenantA->id)->create([
            'email' => 'lama@sma1.smartschool.id',
            'password' => 'sandi-lama-123',
        ]);
        $student->update(['user_id' => $account->id]);
        $hashLama = $account->password;

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'baru@sma1.smartschool.id',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'email' => 'baru@sma1.smartschool.id',
        ]);
        $this->assertSame($hashLama, $account->fresh()->password, 'Kata sandi tidak boleh berubah bila field dikosongkan.');
    }

    public function test_linked_account_password_can_be_reset(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
        $account = User::factory()->siswa($this->tenantA->id)->create([
            'email' => 'ghaffar@sma1.smartschool.id',
            'password' => 'sandi-lama-123',
        ]);
        $student->update(['user_id' => $account->id]);

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@sma1.smartschool.id',
                'account_password' => 'sandi-baru-456',
                'account_password_confirmation' => 'sandi-baru-456',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('sandi-baru-456', $account->fresh()->password));
    }

    public function test_duplicate_account_email_rolls_back_the_whole_student_save(): void
    {
        User::factory()->siswa($this->tenantA->id)->create(['email' => 'bentrok@sma1.smartschool.id']);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'bentrok@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
            ]))
            ->assertSessionHasErrors('account_email');

        $this->assertDatabaseMissing('students', ['nisn' => '8677413804']);
        $this->assertSame(1, User::where('email', 'bentrok@sma1.smartschool.id')->count());
    }

    public function test_new_account_requires_email_and_password(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload(['create_account' => '1']))
            ->assertSessionHasErrors(['account_email', 'account_password']);

        $this->assertDatabaseMissing('students', ['nisn' => '8677413804']);
    }

    public function test_account_password_confirmation_must_match(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'beda-sekali',
            ]))
            ->assertSessionHasErrors('account_password');

        $this->assertDatabaseMissing('students', ['nisn' => '8677413804']);
    }

    public function test_account_password_shorter_than_eight_characters_is_rejected(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@sma1.smartschool.id',
                'account_password' => 'pendek',
                'account_password_confirmation' => 'pendek',
            ]))
            ->assertSessionHasErrors('account_password');

        $this->assertDatabaseMissing('users', ['email' => 'ghaffar@sma1.smartschool.id']);
    }

    public function test_account_cannot_be_created_without_users_manage_permission(): void
    {
        $this->revokeUsersManage($this->adminA);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
            ]))
            ->assertSessionHasErrors('create_account');

        $this->assertDatabaseMissing('users', ['email' => 'ghaffar@sma1.smartschool.id']);
        $this->assertDatabaseMissing('students', ['nisn' => '8677413804']);
    }

    public function test_student_can_still_be_saved_without_users_manage_permission(): void
    {
        $this->revokeUsersManage($this->adminA);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
    }

    public function test_account_tenant_comes_from_the_session_and_not_from_the_form(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'ghaffar@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
                'tenant_id' => $this->tenantB->id,
            ]))
            ->assertSessionHasNoErrors();

        $student = Student::where('nisn', '8677413804')->sole();

        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    public function test_account_email_cannot_be_stolen_from_another_tenant(): void
    {
        User::factory()->siswa($this->tenantB->id)->create(['email' => 'tenant-b@sma1.smartschool.id']);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'tenant-b@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
            ]))
            ->assertSessionHasErrors('account_email');

        $this->assertDatabaseHas('users', [
            'email' => 'tenant-b@sma1.smartschool.id',
            'tenant_id' => $this->tenantB->id,
            'role' => 'siswa',
        ]);
        $this->assertDatabaseMissing('students', ['nisn' => '8677413804']);
    }

    public function test_student_detail_page_shows_the_linked_account_email(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
        $account = User::factory()->siswa($this->tenantA->id)->create(['email' => 'ghaffar@sma1.smartschool.id']);
        $student->update(['user_id' => $account->id]);

        $this->actingAs($this->adminA)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('ghaffar@sma1.smartschool.id');
    }

    public function test_student_detail_page_offers_account_creation_when_missing(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);

        $this->actingAs($this->adminA)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Buat akun login');
    }

    public function test_siswa_account_can_open_own_student_record(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
            'name' => 'Ghaffar Bintang',
        ]);
        $account = User::factory()->siswa($this->tenantA->id)->create([
            'email' => 'ghaffar@sma1.smartschool.id',
            'password' => 'rahasia-siswa',
        ]);
        $student->update(['user_id' => $account->id]);

        $this->actingAs($account)
            ->get(route('students.progress', $student))
            ->assertOk();
    }

    public function test_siswa_account_cannot_open_another_students_record(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
        $account = User::factory()->siswa($this->tenantA->id)->create([
            'email' => 'ghaffar@sma1.smartschool.id',
        ]);
        $student->update(['user_id' => $account->id]);

        $lain = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '1234567890',
        ]);

        $this->actingAs($account)
            ->get(route('students.progress', $lain))
            ->assertForbidden();
    }

    public function test_siswa_account_without_a_link_cannot_read_any_student_record(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
        $yatim = User::factory()->siswa($this->tenantA->id)->create([
            'email' => 'tanpa-tautan@sma1.smartschool.id',
        ]);

        $this->assertNull($student->user_id);

        $this->actingAs($yatim)
            ->get(route('students.progress', $student))
            ->assertForbidden();
    }

    public function test_linked_account_with_wrong_role_is_not_edited_from_the_student_form(): void
    {
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'nisn' => '8677413804',
        ]);
        $guru = User::factory()->guru($this->tenantA->id)->create(['email' => 'guru@sma1.smartschool.id']);
        $student->update(['user_id' => $guru->id]);

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), $this->studentPayload([
                'create_account' => '1',
                'account_email' => 'diubah@sma1.smartschool.id',
                'account_password' => 'rahasia-siswa',
                'account_password_confirmation' => 'rahasia-siswa',
            ]))
            ->assertSessionHasErrors('create_account');

        $this->assertSame('guru@sma1.smartschool.id', $guru->fresh()->email, 'Akun non-siswa tidak boleh diubah lewat form siswa.');
    }
}
