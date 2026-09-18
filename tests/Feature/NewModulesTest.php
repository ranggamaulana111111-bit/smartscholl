<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    private User $siswa;

    private Tenant $tenant;

    private AcademicYear $year;

    private Rombel $rombel;

    private Subject $subject;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();

        $this->admin = User::factory()->adminSekolah($tenant->id)->create();

        $this->guru = User::factory()->guru($tenant->id)->create();

        $this->siswa = User::factory()->siswa($tenant->id)->create();

        $this->year = AcademicYear::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $this->subject = Subject::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->rombel = Rombel::factory()->create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $this->year->id,
            'homeroom_teacher_id' => $this->guru->id,
        ]);

        $this->student = Student::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $this->siswa->id,
            'rombel_id' => $this->rombel->id,
        ]);

        Schedule::factory()->create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
        ]);

        $this->tenant = $tenant;
    }

    // ─── Assessment Export / Import ────────────────────────────────

    public function test_guru_can_export_assessment_grades_as_csv(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
        ]);

        $assessment->grades()->create([
            'tenant_id' => $this->guru->tenant_id,
            'student_id' => $this->student->id,
            'score' => 85,
            'note' => 'Tugas bagus',
        ]);

        $response = $this->actingAs($this->guru)
            ->get(route('assessments.export', $assessment));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString($this->student->nisn, $content);
        $this->assertStringContainsString('85', $content);
    }

    public function test_guru_can_import_grades_from_csv(): void
    {
        Storage::fake('public');

        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
        ]);

        $csv = "NISN,Nama,Nilai,Catatan\n{$this->student->nisn},{$this->student->name},90,Imported\n";

        $file = UploadedFile::fake()->createWithContent('grades.csv', $csv);

        $response = $this->actingAs($this->guru)
            ->post(route('assessments.import', $assessment), [
                'file' => $file,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('assessment_grades', [
            'tenant_id' => $assessment->tenant_id,
            'assessment_id' => $assessment->id,
            'student_id' => $this->student->id,
            'score' => 90,
            'note' => 'Imported',
        ]);
    }

    public function test_siswa_cannot_export_assessment(): void
    {
        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
        ]);

        $this->actingAs($this->siswa)
            ->get(route('assessments.export', $assessment))
            ->assertForbidden();
    }

    // ─── Assignment Module ─────────────────────────────────────────

    public function test_guru_can_create_assignment(): void
    {
        $response = $this->actingAs($this->guru)
            ->post(route('assignments.store'), [
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'title' => 'PR Matematika',
                'description' => 'Kerjakan halaman 10',
                'deadline_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'title' => 'PR Matematika',
        ]);
    }

    public function test_siswa_can_submit_assignment(): void
    {
        Storage::fake('public');

        $assignment = Assignment::factory()->create([
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
            'deadline_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->siswa)
            ->post(route('assignments.submit', $assignment), [
                'note' => 'Sudah selesai pak',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('assignment_submissions', [
            'tenant_id' => $assignment->tenant_id,
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'note' => 'Sudah selesai pak',
        ]);
    }

    public function test_siswa_cannot_see_other_rombel_assignment(): void
    {
        $otherRombel = Rombel::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'academic_year_id' => $this->year->id,
            'name' => 'XI-1',
        ]);

        $assignment = Assignment::factory()->create([
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $otherRombel->id,
            'academic_year_id' => $this->year->id,
            'deadline_at' => now()->addDays(7),
        ]);

        $this->actingAs($this->siswa)
            ->get(route('assignments.show', $assignment))
            ->assertForbidden();
    }

    // ─── Student Progress & Rapor ──────────────────────────────────

    public function test_admin_can_view_student_progress(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('students.progress', $this->student));

        $response->assertOk()->assertViewIs('students.progress');
    }

    public function test_guru_can_view_student_progress(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('students.progress', $this->student));

        $response->assertOk();
    }

    public function test_siswa_can_view_own_progress(): void
    {
        $response = $this->actingAs($this->siswa)
            ->get(route('students.progress', $this->student));

        $response->assertOk();
    }

    public function test_siswa_cannot_view_other_student_progress(): void
    {
        $otherStudent = Student::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'rombel_id' => $this->rombel->id,
        ]);

        $this->actingAs($this->siswa)
            ->get(route('students.progress', $otherStudent))
            ->assertForbidden();
    }

    public function test_admin_can_view_rapor(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('students.rapor', $this->student));

        $response->assertOk()->assertViewIs('students.rapor');
    }

    // ─── Audit Trail ───────────────────────────────────────────────

    public function test_audit_log_recorded_on_assessment_store(): void
    {
        $response = $this->actingAs($this->guru)
            ->post(route('assessments.store'), [
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'category' => 'tugas',
                'title' => 'Tugas 1',
                'max_score' => 100,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->guru->tenant_id,
            'user_id' => $this->guru->id,
            'action' => 'create',
            'entity_type' => 'Assessment',
        ]);
    }

    public function test_audit_log_recorded_on_journal_create(): void
    {
        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'schedule_id' => Schedule::where('user_id', $this->guru->id)->first()->id,
                'date' => now()->toDateString(),
                'topic' => 'Aljabar',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->guru->tenant_id,
            'user_id' => $this->guru->id,
            'action' => 'create',
            'entity_type' => 'Journal',
        ]);
    }

    public function test_audit_log_recorded_on_assignment_submit(): void
    {
        Storage::fake('public');

        $assignment = Assignment::factory()->create([
            'tenant_id' => $this->guru->tenant_id,
            'teacher_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->year->id,
            'deadline_at' => now()->addDays(7),
        ]);

        $this->actingAs($this->siswa)
            ->post(route('assignments.submit', $assignment), [
                'note' => 'Tugas terlampir',
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $assignment->tenant_id,
            'user_id' => $this->siswa->id,
            'action' => 'submit',
            'entity_type' => 'Assignment',
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        AuditLog::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('audit-logs.index'));

        $response->assertOk()->assertViewIs('audit-logs.index');
    }

    public function test_guru_cannot_view_audit_logs(): void
    {
        $this->actingAs($this->guru)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_schedule_delete_recorded_in_audit(): void
    {
        $schedule = Schedule::factory()->create([
            'tenant_id' => $this->admin->tenant_id,
            'user_id' => $this->guru->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('schedules.destroy', $schedule));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->admin->tenant_id,
            'user_id' => $this->admin->id,
            'action' => 'delete',
            'entity_type' => 'Schedule',
        ]);
    }
}
