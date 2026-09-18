<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCorrectnessTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $guru;

    private AcademicYear $year;

    private Rombel $rombel;

    private Subject $subject;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'p1.smartschool.id']);
        $this->admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $this->guru = User::factory()->guru($this->tenant->id)->create();

        $this->year = AcademicYear::factory()->active()->create([
            'tenant_id' => $this->tenant->id,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);

        $this->rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-1',
            'homeroom_teacher_id' => $this->guru->id,
        ]);

        $this->subject = Subject::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Matematika',
            'code' => 'MTK',
        ]);

        $this->student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039000001',
        ]);
    }

    // P1-1: Jendela presensi orang tua = 7 hari inklusif.

    public function test_parent_dashboard_lists_attendance_within_last_seven_days(): void
    {
        $parent = $this->makeParentLink();

        $sixDaysAgo = Attendance::factory()
            ->recordedAt(now()->subDays(6)->toDateString(), '07:00:00')
            ->create($this->attendanceAttributes());

        $today = Attendance::factory()
            ->recordedAt(now()->toDateString(), '07:05:00')
            ->create($this->attendanceAttributes());

        $eightDaysAgo = Attendance::factory()
            ->recordedAt(now()->subDays(8)->toDateString(), '07:00:00')
            ->create($this->attendanceAttributes());

        $feed = $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->viewData('recentAttendance');

        $ids = $feed->pluck('id');

        $this->assertTrue($ids->contains($sixDaysAgo->id));
        $this->assertTrue($ids->contains($today->id));
        $this->assertFalse($ids->contains($eightDaysAgo->id));
    }

    // P1-2: Nilai dan EWS orang tua dibatasi tahun ajaran rombel anak saat ini.

    public function test_parent_average_scores_ignore_previous_academic_year(): void
    {
        $parent = $this->makeParentLink();

        $currentAssessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $this->subject->id,
        ]);

        $previousYear = AcademicYear::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '2024/2025',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $previousAssessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $previousYear->id,
            'subject_id' => $this->subject->id,
        ]);

        AssessmentGrade::factory()->create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $currentAssessment->id,
            'student_id' => $this->student->id,
            'score' => 90,
        ]);

        AssessmentGrade::factory()->create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $previousAssessment->id,
            'student_id' => $this->student->id,
            'score' => 10,
        ]);

        $avgScores = $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->viewData('avgScores');

        $this->assertSame(90.0, $avgScores->get($this->student->id)['overall']);
    }

    public function test_parent_warnings_ignore_triggers_outside_current_academic_year(): void
    {
        $parent = $this->makeParentLink();

        $current = EarlyWarningLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $this->student->id,
            'trigger_date' => now()->toDateString(),
            'is_resolved' => false,
        ]);

        EarlyWarningLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $this->student->id,
            'trigger_date' => now()->subYears(2)->toDateString(),
            'is_resolved' => false,
        ]);

        $warnings = $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->viewData('warnings');

        $this->assertSame([$current->id], $warnings->pluck('id')->all());
    }

    public function test_parent_data_is_empty_for_child_without_rombel(): void
    {
        $parent = User::factory()->orangTua($this->tenant->id)->create();

        $homeless = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => null,
            'nisn' => '0039000002',
        ]);

        StudentParent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $parent->id,
            'student_id' => $homeless->id,
        ]);

        $response = $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk();

        $this->assertFalse($response->viewData('avgScores')->has($homeless->id));
        $this->assertCount(0, $response->viewData('warnings'));
    }

    // P1-3: Ringkasan kehadiran hanya menghitung sesi pelajaran, bukan gerbang.

    public function test_attendance_summary_excludes_gate_rows_and_double_counting(): void
    {
        $dayOne = now()->subDays(2)->toDateString();
        $dayTwo = now()->subDays(1)->toDateString();
        $dayThree = now()->toDateString();

        Attendance::factory()->lesson()->create(['date' => $dayOne, 'status' => 'hadir'] + $this->attendanceAttributes());
        Attendance::factory()->lesson()->create(['date' => $dayTwo, 'status' => 'hadir'] + $this->attendanceAttributes());
        Attendance::factory()->lesson()->create(['date' => $dayThree, 'status' => 'alpha'] + $this->attendanceAttributes());
        Attendance::factory()->gateIn()->create(['date' => $dayOne] + $this->attendanceAttributes());
        Attendance::factory()->gateOut()->create(['date' => $dayOne] + $this->attendanceAttributes());

        $attendance = $this->actingAs($this->admin)
            ->get(route('students.show', $this->student))
            ->assertOk()
            ->viewData('attendance');

        $this->assertSame(
            ['hadir' => 2, 'sakit' => 0, 'izin' => 0, 'alpha' => 1, 'total' => 3],
            $attendance
        );
    }

    // P1-4: Jurnal tertunda berdasarkan jadwal hari ini, bukan kolom user_id NULL.

    public function test_admin_pending_journals_include_today_schedule_without_journal(): void
    {
        $todaySchedule = $this->makeSchedule(now()->dayOfWeekIso);
        $otherDaySchedule = $this->makeSchedule($this->otherWeekday());

        $pending = $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->viewData('pendingJournals');

        $this->assertTrue($pending->pluck('id')->contains($todaySchedule->id));
        $this->assertFalse($pending->pluck('id')->contains($otherDaySchedule->id));
    }

    public function test_pending_journals_remove_schedule_once_journal_is_filled(): void
    {
        $schedule = $this->makeSchedule(now()->dayOfWeekIso);

        Journal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
        ]);

        $pending = $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->viewData('pendingJournals');

        $this->assertFalse($pending->pluck('id')->contains($schedule->id));
    }

    public function test_guru_pending_journals_only_include_own_schedule(): void
    {
        $otherGuru = User::factory()->guru($this->tenant->id)->create();

        $own = $this->makeSchedule(now()->dayOfWeekIso, $this->guru->id);
        $foreign = $this->makeSchedule(now()->dayOfWeekIso, $otherGuru->id);

        $pending = $this->actingAs($this->guru)
            ->get(route('dashboard'))
            ->assertOk()
            ->viewData('pendingJournals');

        $this->assertTrue($pending->pluck('id')->contains($own->id));
        $this->assertFalse($pending->pluck('id')->contains($foreign->id));
    }

    // P1-5: Tanggal jurnal masa lalu diizinkan untuk pengisian tertunda.

    public function test_journal_with_past_date_is_accepted_for_backfill(): void
    {
        $past = now()->subDays(3)->toDateString();

        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'subject_id' => $this->subject->id,
                'date' => $past,
                'topic' => 'Materi tertunda',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('journals.index'));

        $journal = Journal::where('user_id', $this->guru->id)->firstOrFail();

        $this->assertSame($past, $journal->date->toDateString());
        $this->assertSame('Materi tertunda', $journal->topic);
    }

    // P1-6: Guru dengan jadwal tidak boleh dihapus, dan FK melindungi di level DB.

    public function test_teacher_with_schedule_cannot_be_deleted(): void
    {
        $teacher = Teacher::factory()->withUser($this->guru)->create(['tenant_id' => $this->tenant->id]);
        $this->makeSchedule(1, $this->guru->id);

        $this->actingAs($this->admin)
            ->delete(route('teachers.destroy', $teacher))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
    }

    public function test_teacher_without_schedule_can_be_deleted(): void
    {
        $teacher = Teacher::factory()->withUser($this->guru)->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->delete(route('teachers.destroy', $teacher))
            ->assertRedirect(route('teachers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }

    public function test_user_with_schedule_is_protected_by_foreign_key(): void
    {
        $this->makeSchedule(1, $this->guru->id);

        $this->expectException(QueryException::class);

        $this->guru->delete();
    }

    // P1-7: Riwayat rombel tidak boleh punya lebih dari satu baris terbuka.

    public function test_student_cannot_have_two_open_rombel_histories(): void
    {
        $this->student->rombelHistories()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'entered_at' => now()->toDateString(),
        ]);

        $this->expectException(QueryException::class);

        $this->student->rombelHistories()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'entered_at' => now()->toDateString(),
        ]);
    }

    // P1-8: Jurnal tanpa jadwal tetap unik per guru, tanggal, rombel, dan mapel.

    public function test_duplicate_unscheduled_journal_is_rejected(): void
    {
        Journal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'schedule_id' => null,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'subject_id' => $this->subject->id,
                'date' => now()->toDateString(),
                'topic' => 'Duplikat',
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('journals', 1);
    }

    public function test_unscheduled_journal_for_different_subject_is_allowed(): void
    {
        $otherSubject = Subject::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Fisika',
            'code' => 'FIS',
        ]);

        Journal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'schedule_id' => null,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'subject_id' => $otherSubject->id,
                'date' => now()->toDateString(),
                'topic' => 'Mapel lain',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('journals', 2);
    }

    public function test_database_blocks_duplicate_unscheduled_journal(): void
    {
        $attributes = [
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'schedule_id' => null,
            'date' => now()->toDateString(),
        ];

        Journal::factory()->create($attributes);

        $this->expectException(QueryException::class);

        Journal::factory()->create($attributes);
    }

    // NISN: unik per tenant, bukan lintas tenant.

    public function test_same_nisn_is_allowed_across_tenants(): void
    {
        $otherTenant = Tenant::factory()->create(['domain' => 'p1-lain.smartschool.id']);

        $other = Student::factory()->create([
            'tenant_id' => $otherTenant->id,
            'nisn' => $this->student->nisn,
        ]);

        $this->assertDatabaseHas('students', [
            'id' => $other->id,
            'tenant_id' => $otherTenant->id,
            'nisn' => $this->student->nisn,
        ]);
    }

    public function test_database_blocks_duplicate_nisn_within_tenant(): void
    {
        $this->expectException(QueryException::class);

        Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'nisn' => $this->student->nisn,
        ]);
    }

    private function makeParentLink(): User
    {
        $parent = User::factory()->orangTua($this->tenant->id)->create();

        StudentParent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $parent->id,
            'student_id' => $this->student->id,
        ]);

        return $parent;
    }

    /**
     * @return array<string, int|string>
     */
    private function attendanceAttributes(): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'student_id' => $this->student->id,
        ];
    }

    private function makeSchedule(int $isoWeekday, ?int $userId = null): Schedule
    {
        return Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $userId ?? $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => $isoWeekday,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);
    }

    private function otherWeekday(): int
    {
        return now()->dayOfWeekIso === 7 ? 1 : now()->dayOfWeekIso + 1;
    }
}
