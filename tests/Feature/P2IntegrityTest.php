<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class P2IntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private User $admin;

    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'p2a.smartschool.id']);
        $this->otherTenant = Tenant::factory()->create(['domain' => 'p2b.smartschool.id']);
        $this->admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $this->otherAdmin = User::factory()->adminSekolah($this->otherTenant->id)->create();
    }

    public function test_academic_year_with_rombels_cannot_be_deleted_via_route(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id]);
        Rombel::factory()->create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $year->id]);

        $this->actingAs($this->admin)
            ->delete(route('academic-years.destroy', $year))
            ->assertRedirect();

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    public function test_academic_year_with_schedule_cannot_be_deleted(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $year->id]);
        $guru = User::factory()->guru($this->tenant->id)->create();

        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'user_id' => $guru->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'day_of_week' => 1,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('academic-years.destroy', $year))
            ->assertRedirect();

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    public function test_empty_academic_year_can_be_deleted(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->delete(route('academic-years.destroy', $year))
            ->assertRedirect(route('academic-years.index'));

        $this->assertDatabaseMissing('academic_years', ['id' => $year->id]);
    }

    public function test_academic_year_deletion_is_restricted_at_db_level(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id]);
        Rombel::factory()->create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $year->id]);

        $this->expectException(QueryException::class);

        $year->delete();
    }

    public function test_create_rombel_logs_audit(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->post(route('rombels.store'), [
                'academic_year_id' => $year->id,
                'name' => 'X-2',
                'grade_level' => '10',
            ])
            ->assertRedirect(route('rombels.index'));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'action' => 'create',
            'entity_type' => 'Rombel',
        ]);
    }

    public function test_create_teacher_logs_audit(): void
    {
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matematika']);

        $this->actingAs($this->admin)
            ->post(route('teachers.store'), [
                'name' => 'Budi Hartawan',
                'subject_id' => $subject->id,
                'employment_status' => 'asn',
            ])
            ->assertRedirect(route('teachers.index'));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'action' => 'create',
            'entity_type' => 'Teacher',
        ]);
    }

    public function test_update_academic_year_logs_audit(): void
    {
        $year = AcademicYear::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '2025/2026',
        ]);

        $this->actingAs($this->admin)
            ->put(route('academic-years.update', $year), [
                'name' => '2026/2027',
                'semester' => 'ganjil',
                'start_date' => '2026-07-13',
                'end_date' => '2027-06-30',
            ])
            ->assertRedirect(route('academic-years.index'));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'action' => 'update',
            'entity_type' => 'AcademicYear',
            'entity_id' => $year->id,
        ]);
    }

    public function test_teacher_can_be_stored_with_subject_id(): void
    {
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matematika']);

        $this->actingAs($this->admin)
            ->post(route('teachers.store'), [
                'nuptk' => '7755766655110002',
                'name' => 'Siti Rahayu',
                'subject_id' => $subject->id,
                'employment_status' => 'gty',
            ])
            ->assertRedirect(route('teachers.index'));

        $this->assertDatabaseHas('teachers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Siti Rahayu',
            'subject_id' => $subject->id,
        ]);
    }

    public function test_teacher_requires_primary_subject(): void
    {
        $this->actingAs($this->admin)
            ->post(route('teachers.store'), [
                'name' => 'Budi Hartawan',
                'employment_status' => 'asn',
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseMissing('teachers', ['name' => 'Budi Hartawan']);
    }

    public function test_teacher_subject_must_belong_to_same_tenant(): void
    {
        $subjectOther = Subject::factory()->create(['tenant_id' => $this->otherTenant->id]);

        $this->actingAs($this->admin)
            ->post(route('teachers.store'), [
                'nuptk' => '7755766655110003',
                'name' => 'Agus Wijaya',
                'subject_id' => $subjectOther->id,
                'employment_status' => 'asn',
            ])
            ->assertSessionHasErrors('subject_id');
    }

    public function test_teacher_primary_subject_cannot_change_while_schedule_exists(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        $currentSubject = Subject::factory()->create(['tenant_id' => $this->tenant->id]);
        $newSubject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Fisika']);
        $teacher = Teacher::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $currentSubject->id,
        ]);
        $rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
        ]);
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'teacher_id' => $teacher->id,
            'user_id' => $teacher->user_id,
            'subject_id' => $currentSubject->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('teachers.update', $teacher), [
                'nuptk' => '7755766655110999',
                'nip' => $teacher->nip,
                'name' => $teacher->name,
                'subject_id' => $newSubject->id,
                'employment_status' => $teacher->employment_status,
            ])
            ->assertRedirect(route('teachers.index'))
            ->assertSessionHasErrors('subject_id');

        $this->assertSame($currentSubject->id, $teacher->fresh()->subject_id);
    }

    public function test_teacher_show_displays_subject_name(): void
    {
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Biologi']);
        $teacher = Teacher::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dewi Lestari',
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Biologi');
    }

    public function test_teacher_show_falls_back_to_legacy_subject_text(): void
    {
        $teacher = Teacher::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Fajar Nugroho',
            'subject_id' => null,
            'subject_text' => 'Prakarya',
        ]);

        $this->actingAs($this->admin)
            ->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Prakarya');
    }

    public function test_admin_dashboard_shows_teacher_activity_stats(): void
    {
        $year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        $guru = User::factory()->guru($this->tenant->id)->create();
        $guruIdle = User::factory()->guru($this->tenant->id)->create();
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id]);
        $teacher = Teacher::factory()->withUser($guru)->create(['tenant_id' => $this->tenant->id, 'subject_id' => $subject->id]);
        Teacher::factory()->withUser($guruIdle)->create(['tenant_id' => $this->tenant->id, 'subject_id' => $subject->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $year->id]);

        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'teacher_id' => $teacher->id,
            'user_id' => $guru->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'day_of_week' => now()->dayOfWeekIso,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Guru Mengajar Hari Ini')
            ->assertSee('Guru Tanpa Jadwal Hari Ini');
    }

    public function test_student_progress_shows_trend_across_academic_years(): void
    {
        $yearA = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id, 'name' => '2024/2025', 'semester' => 'ganjil']);
        $yearB = AcademicYear::factory()->create(['tenant_id' => $this->tenant->id, 'name' => '2025/2026', 'semester' => 'genap']);
        $rombelA = Rombel::factory()->create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $yearA->id, 'name' => 'XI-A']);
        $rombelB = Rombel::factory()->create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $yearB->id, 'name' => 'XII-A']);
        $student = Student::factory()->create(['tenant_id' => $this->tenant->id, 'rombel_id' => $rombelB->id]);
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matematika']);

        $assessmentA = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $yearA->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombelA->id,
        ]);
        $assessmentB = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $yearB->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombelB->id,
        ]);

        AssessmentGrade::factory()->create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessmentA->id, 'student_id' => $student->id, 'score' => 70]);
        AssessmentGrade::factory()->create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessmentB->id, 'student_id' => $student->id, 'score' => 85]);

        $this->actingAs($this->admin)
            ->get(route('students.progress', $student))
            ->assertOk()
            ->assertSee('Tren Nilai per Tahun Ajaran')
            ->assertSee('2024/2025')
            ->assertSee('2025/2026');
    }

    public function test_subject_index_uses_renamed_teacher_columns(): void
    {
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Biologi']);

        Teacher::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Guru FK', 'subject_id' => $subject->id, 'subject_text' => null]);
        Teacher::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Guru Teks', 'subject_id' => null, 'subject_text' => 'biologi']);

        $this->actingAs($this->admin)
            ->get(route('subjects.index'))
            ->assertOk()
            ->assertSee('Biologi')
            ->assertSee('Guru FK')
            ->assertSee('Guru Teks');

        $this->actingAs($this->admin)
            ->get(route('subjects.show', $subject))
            ->assertOk()
            ->assertSee('Guru FK');
    }

    public function test_subject_store_persists_active_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.store'), ['name' => 'TIK', 'code' => 'TIK'])
            ->assertRedirect(route('subjects.index'));

        $this->assertTrue(Subject::where('tenant_id', $this->tenant->id)->where('name', 'TIK')->value('is_active'));

        $this->actingAs($this->admin)
            ->post(route('subjects.store'), ['name' => 'Sejarah', 'is_active' => '0'])
            ->assertRedirect(route('subjects.index'));

        $this->assertFalse(Subject::where('tenant_id', $this->tenant->id)->where('name', 'Sejarah')->value('is_active'));
    }
}
