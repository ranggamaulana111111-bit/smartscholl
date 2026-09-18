<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'sman1.smartschool.id']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'smpharapan.smartschool.id']);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();
        $this->adminB = User::factory()->adminSekolah($this->tenantB->id)->create();
    }

    public function test_guest_cannot_access_students_page(): void
    {
        $this->get(route('students.index'))->assertRedirect(route('login'));
    }

    public function test_year_can_be_created_by_admin(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('academic-years.store'), [
                'name' => '2026/2027',
                'semester' => 'ganjil',
                'start_date' => '2026-07-13',
                'end_date' => '2026-12-18',
                'is_active' => '1',
            ])
            ->assertRedirect(route('academic-years.index'));

        $this->assertDatabaseHas('academic_years', [
            'tenant_id' => $this->tenantA->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
    }

    public function test_only_one_active_year_allowed_per_tenant(): void
    {
        AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);

        $this->actingAs($this->adminA)
            ->post(route('academic-years.store'), [
                'name' => '2027/2028',
                'semester' => 'ganjil',
                'start_date' => '2027-07-12',
                'end_date' => '2027-12-17',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('is_active');
    }

    public function test_rombel_can_be_created(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->actingAs($this->adminA)
            ->post(route('rombels.store'), [
                'academic_year_id' => $year->id,
                'name' => 'X-1',
                'grade_level' => 'X',
                'homeroom_teacher_id' => null,
            ])
            ->assertRedirect(route('rombels.index'));

        $this->assertDatabaseHas('rombels', [
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
            'name' => 'X-1',
        ]);
    }

    public function test_student_can_be_created_and_scoped(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
        ]);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), [
                'nisn' => '0039123456',
                'nis' => '24001',
                'name' => 'Andi Santoso',
                'rombel_id' => $rombel->id,
                'gender' => 'L',
                'birth_date' => '2009-05-12',
                'birth_place' => 'Jakarta',
                'address' => 'Jl. Melati',
                'phone' => '081234567890',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'tenant_id' => $this->tenantA->id,
            'nisn' => '0039123456',
            'name' => 'Andi Santoso',
        ]);
    }

    public function test_teacher_can_be_created(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('teachers.store'), [
                'nuptk' => '7755766655110001',
                'nip' => '198001012010011001',
                'name' => 'Budi Hartawan',
                'subject' => 'Matematika',
                'employment_status' => 'asn',
                'address' => 'Jl. Kenanga',
                'phone' => '081298765432',
            ])
            ->assertRedirect(route('teachers.index'));

        $this->assertDatabaseHas('teachers', [
            'tenant_id' => $this->tenantA->id,
            'nuptk' => '7755766655110001',
            'name' => 'Budi Hartawan',
        ]);
    }

    public function test_admin_sees_only_own_tenant_students_on_index(): void
    {
        $yearA = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombelA = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $yearA->id]);
        Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombelA->id, 'name' => 'Siswa Tenant A']);

        $yearB = AcademicYear::factory()->create(['tenant_id' => $this->tenantB->id]);
        $rombelB = Rombel::factory()->create(['tenant_id' => $this->tenantB->id, 'academic_year_id' => $yearB->id]);
        Student::factory()->create(['tenant_id' => $this->tenantB->id, 'rombel_id' => $rombelB->id, 'name' => 'Siswa Tenant B']);

        $this->actingAs($this->adminA)
            ->get(route('students.index'))
            ->assertOk()
            ->assertSee('Siswa Tenant A')
            ->assertDontSee('Siswa Tenant B');
    }

    public function test_duplicate_nisn_rejected_per_tenant(): void
    {
        $yearA = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombelA = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $yearA->id]);
        Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombelA->id, 'nisn' => '0039123456']);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), [
                'nisn' => '0039123456',
                'name' => 'Andi Santoso',
                'gender' => 'L',
                'birth_date' => '2009-05-12',
            ])
            ->assertSessionHasErrors('nisn');
    }

    public function test_student_can_be_updated_and_deleted(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), [
                'nisn' => $student->nisn,
                'name' => 'Budi Hartono',
                'rombel_id' => $rombel->id,
                'gender' => 'L',
                'birth_date' => '2009-08-24',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Budi Hartono',
        ]);

        $this->actingAs($this->adminA)
            ->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_cross_tenant_rombel_not_usable(): void
    {
        $yearB = AcademicYear::factory()->create(['tenant_id' => $this->tenantB->id]);

        $this->actingAs($this->adminA)
            ->post(route('rombels.store'), [
                'academic_year_id' => $yearB->id,
                'name' => 'X-1',
                'grade_level' => 'X',
            ])
            ->assertSessionHasErrors('academic_year_id');
    }

    public function test_subject_detail_lists_guru_pengajar_and_kelas(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $subject = Subject::factory()->create(['tenant_id' => $this->tenantA->id, 'code' => 'MTK', 'name' => 'Matematika']);
        $guru = User::factory()->guru($this->tenantA->id)->create();
        Schedule::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
            'user_id' => $guru->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->actingAs($this->adminA)
            ->get(route('subjects.show', $subject))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertSee($guru->name)
            ->assertSee($rombel->name);

        $this->actingAs($this->adminA)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee('Guru Pengajar')
            ->assertSee('Kelas');
    }

    public function test_master_data_detail_pages_are_accessible(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id, 'name' => '2026/2027']);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);
        $teacher = Teacher::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->actingAs($this->adminA)
            ->get(route('students.show', $student))
            ->assertOk()
            ->assertSee($student->name);

        $this->actingAs($this->adminA)
            ->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->name);

        $this->actingAs($this->adminA)
            ->get(route('rombels.show', $rombel))
            ->assertOk()
            ->assertSee($rombel->name);

        $this->actingAs($this->adminA)
            ->get(route('academic-years.show', $year))
            ->assertOk()
            ->assertSee('2026/2027');
    }

    public function test_guru_cannot_access_master_data_detail_page(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);

        $this->actingAs($guru)
            ->get(route('students.show', $student))
            ->assertForbidden();
    }

    public function test_student_gets_qr_token_on_create(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('students.store'), [
                'nisn' => '0039123457',
                'name' => 'Citra Lestari',
                'gender' => 'P',
                'birth_date' => '2009-03-02',
            ])
            ->assertRedirect(route('students.index'));

        $student = Student::where('nisn', '0039123457')->firstOrFail();

        $this->assertNotNull($student->qr_token);
        $this->assertSame(32, strlen($student->qr_token));
        $this->assertSame('SS:'.$student->qr_token, $student->qrcodePayload());
    }

    public function test_rfid_uid_unique_within_tenant_only(): void
    {
        $yearA = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);
        $rombelA = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $yearA->id]);
        Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombelA->id,
            'rfid_uid' => '04A2B3C4D5',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), [
                'nisn' => '0039123458',
                'name' => 'Dewi Anggraini',
                'gender' => 'P',
                'birth_date' => '2009-04-01',
                'rfid_uid' => '04a2b3c4d5',
            ])
            ->assertSessionHasErrors('rfid_uid');

        $yearB = AcademicYear::factory()->create(['tenant_id' => $this->tenantB->id]);
        $rombelB = Rombel::factory()->create(['tenant_id' => $this->tenantB->id, 'academic_year_id' => $yearB->id]);

        $this->actingAs($this->adminB)
            ->post(route('students.store'), [
                'nisn' => '0039123459',
                'name' => 'Eko Prasetyo',
                'gender' => 'L',
                'birth_date' => '2009-06-11',
                'rfid_uid' => '04A2B3C4D5',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'tenant_id' => $this->tenantB->id,
            'nisn' => '0039123459',
            'rfid_uid' => '04A2B3C4D5',
        ]);
    }

    public function test_regenerate_qr_changes_token(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);
        $oldToken = $student->qr_token;

        $this->actingAs($this->adminA)
            ->post(route('students.regenerate-qr', $student))
            ->assertSessionHas('success');

        $this->assertNotSame($oldToken, $student->fresh()->qr_token);
    }

    public function test_student_delete_blocked_when_has_attendance(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);
        Attendance::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $student->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminA)
            ->delete(route('students.destroy', $student))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_student_delete_blocked_when_has_grades(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);
        $assessment = Assessment::factory()->create(['tenant_id' => $this->tenantA->id]);
        AssessmentGrade::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($this->adminA)
            ->delete(route('students.destroy', $student))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_new_student_rejected_for_inactive_year_rombel(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => false]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), [
                'nisn' => '0039123460',
                'name' => 'Siswa Baru',
                'rombel_id' => $rombel->id,
                'gender' => 'L',
                'birth_date' => '2009-05-12',
            ])
            ->assertSessionHasErrors('rombel_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_cross_tenant_rombel_rejected_for_student(): void
    {
        $yearB = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantB->id]);
        $rombelB = Rombel::factory()->create(['tenant_id' => $this->tenantB->id, 'academic_year_id' => $yearB->id]);

        $this->actingAs($this->adminA)
            ->post(route('students.store'), [
                'nisn' => '0039123461',
                'name' => 'Siswa Nyasar',
                'rombel_id' => $rombelB->id,
                'gender' => 'L',
                'birth_date' => '2009-01-01',
            ])
            ->assertSessionHasErrors('rombel_id');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_student_keeps_current_rombel_when_year_marked_inactive(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombel->id]);

        $year->update(['is_active' => false]);

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), [
                'nisn' => $student->nisn,
                'name' => 'Andi Update',
                'rombel_id' => $rombel->id,
                'gender' => 'L',
                'birth_date' => '2009-08-24',
            ])
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Andi Update']);
    }

    public function test_rombel_history_reentry_keeps_single_open_row(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id]);
        $rombelA = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id, 'name' => 'X-1']);
        $rombelB = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id, 'name' => 'X-2']);
        $student = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombelA->id]);

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student), [
                'nisn' => $student->nisn,
                'name' => $student->name,
                'rombel_id' => $rombelB->id,
                'gender' => 'P',
                'birth_date' => '2009-01-01',
            ])
            ->assertRedirect(route('students.index'));

        $this->actingAs($this->adminA)
            ->put(route('students.update', $student->fresh()), [
                'nisn' => $student->nisn,
                'name' => $student->name,
                'rombel_id' => $rombelA->id,
                'gender' => 'P',
                'birth_date' => '2009-01-01',
            ])
            ->assertRedirect(route('students.index'));

        $student->refresh();

        $openForA = $student->rombelHistories()->where('rombel_id', $rombelA->id)->whereNull('left_at')->count();
        $openForB = $student->rombelHistories()->where('rombel_id', $rombelB->id)->whereNull('left_at')->count();

        $this->assertSame(1, $openForA);
        $this->assertSame(0, $openForB);
    }
}
