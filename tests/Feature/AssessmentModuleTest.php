<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentModuleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $guru;

    private Subject $subject;

    private AcademicYear $year;

    private Rombel $rombel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'sma.test.id']);
        $this->admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $this->guru = User::factory()->guru($this->tenant->id)->create();
        $this->subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matematika']);
        $this->year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        $this->rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-1',
        ]);
    }

    public function test_subject_can_be_created_by_admin(): void
    {
        $this->actingAs($this->admin)
            ->post(route('subjects.store'), [
                'name' => 'Fisika',
                'code' => 'FIS',
                'is_active' => '1',
            ])
            ->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Fisika',
        ]);
    }

    public function test_student_cannot_create_subject(): void
    {
        $student = User::factory()->siswa($this->tenant->id)->create();

        $this->actingAs($student)
            ->post(route('subjects.store'), [
                'name' => 'Fisika',
            ])
            ->assertForbidden();
    }

    public function test_assessment_can_be_created_by_guru(): void
    {
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
        ]);

        $this->actingAs($this->guru)
            ->post(route('assessments.store'), [
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'category' => 'uts',
                'title' => 'UTS Matematika',
                'max_score' => 100,
                'date' => '2026-09-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assessments', [
            'tenant_id' => $this->tenant->id,
            'title' => 'UTS Matematika',
            'teacher_id' => $this->guru->id,
        ]);
    }

    public function test_guru_cannot_create_assessment_outside_schedule(): void
    {
        $rombelLain = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-2',
        ]);

        $this->actingAs($this->guru)
            ->post(route('assessments.store'), [
                'subject_id' => $this->subject->id,
                'rombel_id' => $rombelLain->id,
                'category' => 'uts',
                'title' => 'UTS Kelas Asing',
                'max_score' => 100,
            ])
            ->assertSessionHasErrors('rombel_id');

        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_guru_cannot_create_assessment_for_unscheduled_subject(): void
    {
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
        ]);

        $subjectLain = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Fisika']);

        $this->actingAs($this->guru)
            ->post(route('assessments.store'), [
                'subject_id' => $subjectLain->id,
                'rombel_id' => $this->rombel->id,
                'category' => 'formatif',
                'title' => 'Formatif Fisika',
                'max_score' => 100,
            ])
            ->assertSessionHasErrors('rombel_id');

        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_used_subject_cannot_be_deleted(): void
    {
        Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('subjects.destroy', $this->subject))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('subjects', [
            'id' => $this->subject->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_unused_subject_can_be_deleted(): void
    {
        $unused = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Seni Budaya']);

        $this->actingAs($this->admin)
            ->delete(route('subjects.destroy', $unused))
            ->assertRedirect(route('subjects.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $unused->id]);
    }

    public function test_scores_can_be_bulk_saved(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $this->guru->id,
        ]);

        $student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111222',
        ]);

        $this->actingAs($this->guru)
            ->post(route('assessments.grades', $assessment), [
                'scores' => [$student->id => 85],
                'notes' => [$student->id => 'Bagus'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assessment_grades', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => '85.00',
        ]);
    }

    public function test_score_above_max_is_rejected(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $this->guru->id,
            'max_score' => 100,
        ]);

        $student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111233',
        ]);

        $this->actingAs($this->guru)
            ->post(route('assessments.grades', $assessment), [
                'scores' => [$student->id => 101],
            ])
            ->assertSessionHasErrors('scores.'.$student->id);

        $this->assertDatabaseCount('assessment_grades', 0);
    }

    public function test_grade_outside_assessment_rombel_is_ignored(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $this->guru->id,
        ]);

        $rombelLain = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-2',
        ]);
        $outsider = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $rombelLain->id,
            'nisn' => '0039111244',
        ]);

        $this->actingAs($this->guru)
            ->post(route('assessments.grades', $assessment), [
                'scores' => [$outsider->id => 85],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('assessment_grades', 0);
    }

    public function test_clearing_score_deletes_only_that_student_grade(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $this->guru->id,
        ]);

        $studentA = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111255',
        ]);
        $studentB = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111266',
        ]);

        AssessmentGrade::create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id, 'student_id' => $studentA->id, 'score' => 70]);
        AssessmentGrade::create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id, 'student_id' => $studentB->id, 'score' => 80]);

        $this->actingAs($this->guru)
            ->post(route('assessments.grades', $assessment), [
                'scores' => [$studentA->id => null, $studentB->id => 85],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assessment_grades', [
            'student_id' => $studentB->id,
            'score' => '85.00',
        ]);
        $this->assertDatabaseMissing('assessment_grades', [
            'student_id' => $studentA->id,
        ]);
    }

    public function test_guru_cannot_manage_assessment_of_other_guru(): void
    {
        $guruB = User::factory()->guru($this->tenant->id)->create();

        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $guruB->id,
            'title' => 'Tugas Guru Lain',
        ]);

        $student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111277',
        ]);

        $this->actingAs($this->guru)
            ->get(route('assessments.show', $assessment))
            ->assertForbidden();

        $this->actingAs($this->guru)
            ->post(route('assessments.grades', $assessment), [
                'scores' => [$student->id => 85],
            ])
            ->assertForbidden();
    }

    public function test_csv_template_lists_rombel_students(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $this->guru->id,
        ]);

        Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111288',
            'name' => 'Budi Santoso',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('assessments.template', $assessment))
            ->assertOk();

        $this->assertStringContainsString('0039111288', $response->getContent());
        $this->assertStringContainsString('NISN', $response->getContent());
    }

    public function test_guru_sees_only_own_assessments(): void
    {
        $guruB = User::factory()->guru($this->tenant->id)->create();

        Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->guru->id,
            'title' => 'Tugas Saya',
        ]);
        Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $guruB->id,
            'title' => 'Tugas Guru Lain',
        ]);

        $this->actingAs($this->guru)
            ->get(route('assessments.index'))
            ->assertOk()
            ->assertSee('Tugas Saya')
            ->assertDontSee('Tugas Guru Lain');
    }

    public function test_grade_does_not_leak_across_tenants(): void
    {
        $tenantB = Tenant::factory()->create();
        $studentB = Student::factory()->create(['tenant_id' => $tenantB->id]);

        $otherAssessment = Assessment::factory()->create(['tenant_id' => $tenantB->id]);
        AssessmentGrade::factory()->create([
            'tenant_id' => $tenantB->id,
            'assessment_id' => $otherAssessment->id,
            'student_id' => $studentB->id,
            'score' => 99,
        ]);

        $studentA = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039188777',
        ]);

        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
        ]);
        AssessmentGrade::factory()->create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'student_id' => $studentA->id,
            'score' => 88,
        ]);

        $this->actingAs($this->admin);

        $grades = AssessmentGrade::forCurrentTenant()->get();

        $this->assertTrue($grades->every(fn ($g) => $g->tenant_id === $this->tenant->id));
    }

    public function test_siswa_sees_only_own_grade_in_assessment_detail(): void
    {
        $siswaUser = User::factory()->siswa($this->tenant->id)->create();
        $siswa = Student::factory()->withUser($siswaUser)->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039112001',
            'name' => 'Rangga Putra',
        ]);
        $temannya = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039112002',
            'name' => 'Siti Aminah',
        ]);

        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'teacher_id' => $this->guru->id,
            'title' => 'UTS Matematika',
        ]);

        AssessmentGrade::create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id, 'student_id' => $siswa->id, 'score' => 85]);
        AssessmentGrade::create(['tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id, 'student_id' => $temannya->id, 'score' => 42]);

        $response = $this->actingAs($siswaUser)
            ->get(route('assessments.show', $assessment))
            ->assertOk();

        $this->assertStringContainsString('Rangga Putra', $response->getContent());
        $this->assertStringContainsString('85.00', $response->getContent());
        $this->assertStringNotContainsString('Siti Aminah', $response->getContent());
        $this->assertStringNotContainsString('42.00', $response->getContent());
    }

    public function test_siswa_cannot_view_assessment_outside_own_rombel(): void
    {
        $siswaUser = User::factory()->siswa($this->tenant->id)->create();
        $siswa = Student::factory()->withUser($siswaUser)->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039112003',
        ]);
        $rombelLain = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-2',
        ]);
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $rombelLain->id,
            'teacher_id' => $this->guru->id,
            'title' => 'Penilaian Kelas Lain',
        ]);

        $this->actingAs($siswaUser)
            ->get(route('assessments.show', $assessment))
            ->assertForbidden();
    }

    public function test_rombel_with_students_cannot_be_deleted(): void
    {
        Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039112099',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('rombels.destroy', $this->rombel))
            ->assertRedirect();

        $response->assertSessionHasErrors();

        $this->assertDatabaseHas('rombels', ['id' => $this->rombel->id, 'tenant_id' => $this->tenant->id]);
    }

    public function test_unused_rombel_can_be_deleted(): void
    {
        $unused = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-9',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('rombels.destroy', $unused))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('rombels', ['id' => $unused->id]);
    }

    public function test_student_rombels_transfer_records_history(): void
    {
        $rombelBaru = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-2',
        ]);

        $this->actingAs($this->admin)
            ->post(route('students.store'), [
                'tenant_id' => $this->tenant->id,
                'nisn' => '0039112100',
                'name' => 'Dian Sastro',
                'rombel_id' => $this->rombel->id,
                'gender' => 'P',
                'birth_date' => '2006-05-14',
            ])
            ->assertRedirect();

        $student = Student::where('nisn', '0039112100')->firstOrFail();

        $this->assertDatabaseHas('student_rombel_histories', [
            'student_id' => $student->id,
            'rombel_id' => $this->rombel->id,
            'entered_at' => today()->toDateString(),
            'left_at' => null,
        ]);

        $this->actingAs($this->admin)
            ->put(route('students.update', $student), [
                'nisn' => $student->nisn,
                'name' => $student->name,
                'rombel_id' => $rombelBaru->id,
                'gender' => $student->gender,
                'birth_date' => $student->birth_date->format('Y-m-d'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_rombel_histories', [
            'student_id' => $student->id,
            'rombel_id' => $this->rombel->id,
            'left_at' => today()->toDateString(),
        ]);

        $this->assertDatabaseHas('student_rombel_histories', [
            'student_id' => $student->id,
            'rombel_id' => $rombelBaru->id,
            'entered_at' => today()->toDateString(),
            'left_at' => null,
        ]);
    }
}
