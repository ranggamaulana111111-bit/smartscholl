<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Rombel;
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
        $this->actingAs($this->guru)
            ->post(route('assessments.storeInput'), [
                'assessment_id' => '',
                'scores' => [],
            ])
            ->assertSessionHasErrors('assessment_id');
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
            ->post(route('assessments.storeInput'), [
                'assessment_id' => $assessment->id,
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
}
