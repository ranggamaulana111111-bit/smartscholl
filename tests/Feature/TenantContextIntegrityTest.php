<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantContextIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private User $adminB;

    private User $guruA;

    private User $guruB;

    private AcademicYear $yearA;

    private AcademicYear $yearB;

    private Rombel $rombelA;

    private Rombel $rombelB;

    private Subject $subjectA;

    private Subject $subjectB;

    private Student $studentA;

    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'sman1.smartschool.id']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'smpharapan.smartschool.id']);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();
        $this->adminB = User::factory()->adminSekolah($this->tenantB->id)->create();
        $this->guruA = User::factory()->guru($this->tenantA->id)->create();
        $this->guruB = User::factory()->guru($this->tenantB->id)->create();

        $this->yearA = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantA->id, 'name' => '2026/2027']);
        $this->yearB = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenantB->id, 'name' => '2026/2027']);

        $this->rombelA = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->yearA->id,
            'name' => 'X-1',
            'homeroom_teacher_id' => $this->guruA->id,
        ]);
        $this->rombelB = Rombel::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'academic_year_id' => $this->yearB->id,
            'name' => 'X-1',
        ]);

        $this->subjectA = Subject::factory()->create(['tenant_id' => $this->tenantA->id, 'name' => 'Matematika', 'code' => 'MTK']);
        $this->subjectB = Subject::factory()->create(['tenant_id' => $this->tenantB->id, 'name' => 'Bahasa Indonesia', 'code' => 'BIN']);

        $this->studentA = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $this->rombelA->id,
            'nisn' => '0039111111',
        ]);
        $this->studentB = Student::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'rombel_id' => $this->rombelB->id,
            'nisn' => '0039222222',
        ]);
    }

    public function test_schedule_rejects_cross_tenant_references(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->yearB->id,
                'user_id' => $this->guruB->id,
                'subject_id' => $this->subjectB->id,
                'rombel_id' => $this->rombelB->id,
                'day_of_week' => 1,
                'start_time' => '07:00',
                'end_time' => '08:00',
            ])
            ->assertSessionHasErrors(['academic_year_id', 'user_id', 'subject_id', 'rombel_id']);

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_schedule_rejects_rombel_from_other_academic_year(): void
    {
        $inactiveYear = AcademicYear::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'name' => '2025/2026',
            'is_active' => false,
        ]);
        $foreignRombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $inactiveYear->id,
            'name' => 'XI-1',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->yearA->id,
                'user_id' => $this->guruA->id,
                'subject_id' => $this->subjectA->id,
                'rombel_id' => $foreignRombel->id,
                'day_of_week' => 2,
                'start_time' => '09:00',
                'end_time' => '10:00',
            ])
            ->assertSessionHasErrors('rombel_id');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_journal_rejects_cross_tenant_schedule(): void
    {
        $foreignSchedule = $this->makeSchedule($this->tenantB->id, $this->yearB->id, $this->guruB->id, $this->rombelB->id, $this->subjectB->id);

        $this->actingAs($this->guruA)
            ->post(route('journals.store'), [
                'schedule_id' => $foreignSchedule->id,
                'rombel_id' => $this->rombelB->id,
                'date' => now()->toDateString(),
                'topic' => 'Materi lintas tenant',
            ])
            ->assertSessionHasErrors(['schedule_id', 'rombel_id']);

        $this->assertDatabaseCount('journals', 0);
    }

    public function test_journal_derives_context_from_schedule(): void
    {
        $schedule = $this->makeSchedule($this->tenantA->id, $this->yearA->id, $this->guruA->id, $this->rombelA->id, $this->subjectA->id);

        $this->actingAs($this->guruA)
            ->post(route('journals.store'), [
                'schedule_id' => $schedule->id,
                'rombel_id' => $this->rombelA->id,
                'date' => now()->toDateString(),
                'topic' => 'Persamaan kuadrat',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('journals.index'));

        $journal = Journal::firstOrFail();

        $this->assertSame($schedule->subject_id, $journal->subject_id);
        $this->assertSame($schedule->rombel_id, $journal->rombel_id);
        $this->assertSame($schedule->academic_year_id, $journal->academic_year_id);
    }

    public function test_assessment_rejects_cross_tenant_subject(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('assessments.store'), [
                'subject_id' => $this->subjectB->id,
                'category' => 'tugas',
                'title' => 'Ulangan lintas tenant',
                'max_score' => 100,
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_assessment_rejects_rombel_from_inactive_year(): void
    {
        $inactiveYear = AcademicYear::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'name' => '2025/2026',
            'is_active' => false,
        ]);
        $foreignRombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $inactiveYear->id,
            'name' => 'XI-1',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('assessments.store'), [
                'subject_id' => $this->subjectA->id,
                'rombel_id' => $foreignRombel->id,
                'category' => 'uts',
                'title' => 'UTS semester lalu',
                'max_score' => 100,
            ])
            ->assertSessionHasErrors('rombel_id');

        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_assignment_rejects_cross_tenant_references(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('assignments.store'), [
                'rombel_id' => $this->rombelB->id,
                'subject_id' => $this->subjectB->id,
                'title' => 'Tugas lintas tenant',
                'deadline_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors(['rombel_id', 'subject_id']);

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_manual_attendance_rejects_cross_tenant_student(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.storeManual'), [
                'student_id' => $this->studentB->id,
                'type' => 'gate_in',
                'status' => 'hadir',
                'date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_manual_attendance_rejects_cross_tenant_schedule(): void
    {
        $foreignSchedule = $this->makeSchedule($this->tenantB->id, $this->yearB->id, $this->guruB->id, $this->rombelB->id, $this->subjectB->id);

        $this->actingAs($this->adminA)
            ->post(route('attendance.storeManual'), [
                'student_id' => $this->studentA->id,
                'schedule_id' => $foreignSchedule->id,
                'type' => 'lesson',
                'status' => 'hadir',
                'date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('schedule_id');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_dedupe_key_blocks_duplicate_gate_row(): void
    {
        $attributes = [
            'tenant_id' => $this->tenantA->id,
            'student_id' => $this->studentA->id,
            'schedule_id' => null,
            'type' => 'gate_in',
            'status' => 'hadir',
            'date' => '2026-09-15',
            'time' => '07:00:00',
        ];

        Attendance::factory()->create($attributes);

        $this->expectException(QueryException::class);

        Attendance::factory()->create($attributes);
    }

    public function test_attendance_dedupe_key_allows_distinct_lesson_schedules(): void
    {
        $first = $this->makeSchedule($this->tenantA->id, $this->yearA->id, $this->guruA->id, $this->rombelA->id, $this->subjectA->id);
        $second = $this->makeSchedule($this->tenantA->id, $this->yearA->id, $this->guruA->id, $this->rombelA->id, $this->subjectA->id);

        foreach ([$first, $second] as $schedule) {
            Attendance::factory()->create([
                'tenant_id' => $this->tenantA->id,
                'student_id' => $this->studentA->id,
                'schedule_id' => $schedule->id,
                'type' => 'lesson',
                'status' => 'hadir',
                'date' => '2026-09-15',
                'time' => '07:30:00',
            ]);
        }

        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_guru_dashboard_feed_scoped_to_homeroom(): void
    {
        $otherRombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->yearA->id,
            'name' => 'X-9',
        ]);
        $otherStudent = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $otherRombel->id,
            'nisn' => '0039333333',
        ]);

        $own = Attendance::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $this->studentA->id,
            'type' => 'gate_in',
            'date' => now()->toDateString(),
            'time' => '07:00:00',
        ]);
        Attendance::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $otherStudent->id,
            'type' => 'gate_in',
            'date' => now()->toDateString(),
            'time' => '07:05:00',
        ]);

        $response = $this->actingAs($this->guruA)->get(route('dashboard'))->assertOk();

        $feed = $response->viewData('recentAttendances');

        $this->assertTrue($feed->pluck('id')->contains($own->id));
        $this->assertCount(1, $feed);
    }

    private function makeSchedule(string $tenantId, int $yearId, string $userId, int $rombelId, int $subjectId): Schedule
    {
        return Schedule::factory()->create([
            'tenant_id' => $tenantId,
            'academic_year_id' => $yearId,
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'rombel_id' => $rombelId,
            'day_of_week' => 1,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);
    }
}
