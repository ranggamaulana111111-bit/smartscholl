<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleJournalModuleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $guru;

    private Teacher $teacher;

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
        $this->teacher = $this->makeTeacher($this->guru, $this->subject);
    }

    private function makeTeacher(User $user, Subject $subject): Teacher
    {
        return Teacher::factory()->withUser($user)->create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_admin_can_create_schedule(): void
    {
        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 1,
                'start_time' => '07:30',
                'end_time' => '09:00',
            ])
            ->assertRedirect(route('schedules.index'));

        $this->assertDatabaseHas('schedules', [
            'tenant_id' => $this->tenant->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
        ]);
    }

    public function test_admin_can_create_schedule_for_teacher_without_user_account(): void
    {
        $subject = Subject::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Fisika',
        ]);
        $teacher = Teacher::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => null,
            'subject_id' => $subject->id,
            'employment_status' => 'ptt',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 4,
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertRedirect(route('schedules.index'));

        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $teacher->id,
            'user_id' => null,
            'subject_id' => $subject->id,
        ]);
    }

    public function test_schedule_create_lists_teachers_for_every_employment_status(): void
    {
        $statuses = ['asn', 'gty', 'ptt'];

        foreach ($statuses as $status) {
            $subject = Subject::factory()->create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Mapel '.$status,
            ]);
            Teacher::factory()->create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Guru '.$status,
                'subject_id' => $subject->id,
                'employment_status' => $status,
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('schedules.create'))
            ->assertOk()
            ->assertSee('Guru asn')
            ->assertSee('Guru gty')
            ->assertSee('Guru ptt');
    }

    public function test_schedule_rejects_subject_that_does_not_match_teacher_primary_subject(): void
    {
        $otherSubject = Subject::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Biologi',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $this->teacher->id,
                'subject_id' => $otherSubject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 3,
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_admin_can_create_schedule_without_academic_year_field(): void
    {
        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'teacher_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 2,
                'start_time' => '08:00',
                'end_time' => '09:30',
            ])
            ->assertRedirect(route('schedules.index'));

        $this->assertDatabaseHas('schedules', [
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
        ]);
    }

    public function test_schedule_store_requires_active_academic_year(): void
    {
        $this->year->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'teacher_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 2,
                'start_time' => '08:00',
                'end_time' => '09:30',
            ])
            ->assertSessionHasErrors('academic_year_id');
    }

    public function test_overlapping_schedule_rejected(): void
    {
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 1,
                'start_time' => '08:30',
                'end_time' => '10:00',
            ])
            ->assertSessionHasErrors('start_time');

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_adjacent_schedule_is_allowed(): void
    {
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 1,
                'start_time' => '09:00',
                'end_time' => '10:30',
            ])
            ->assertRedirect(route('schedules.index'));
    }

    public function test_schedule_conflict_for_same_rombel_detected(): void
    {
        $guruB = User::factory()->guru($this->tenant->id)->create();
        $teacherB = $this->makeTeacher($guruB, $this->subject);

        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 3,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $teacherB->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 3,
                'start_time' => '08:00',
                'end_time' => '09:30',
            ])
            ->assertSessionHasErrors('start_time');
    }

    public function test_update_schedule_does_not_conflict_with_itself(): void
    {
        $schedule = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 4,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($this->admin)
            ->put(route('schedules.update', $schedule), [
                'academic_year_id' => $this->year->id,
                'teacher_id' => $this->teacher->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 4,
                'start_time' => '07:30',
                'end_time' => '09:00',
            ])
            ->assertRedirect(route('schedules.index'));
    }

    public function test_guru_can_fill_journal(): void
    {
        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'subject_id' => $this->subject->id,
                'date' => now()->toDateString(),
                'topic' => 'Persamaan Linear',
                'notes' => 'Siswa aktif bertanya',
                'status' => 'closed',
            ])
            ->assertRedirect(route('journals.index'));

        $this->assertDatabaseHas('journals', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->guru->id,
            'rombel_id' => $this->rombel->id,
            'topic' => 'Persamaan Linear',
            'status' => 'closed',
        ]);
    }

    public function test_guru_cannot_fill_journal_for_past_date_tomorrow(): void
    {
        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'date' => now()->addDay()->toDateString(),
                'topic' => 'Masa Depan',
            ])
            ->assertSessionHasErrors('date');
    }

    public function test_duplicate_journal_for_same_schedule_rejected(): void
    {
        $schedule = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 1,
        ]);

        Journal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'rombel_id' => $this->rombel->id,
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'schedule_id' => $schedule->id,
                'date' => now()->toDateString(),
                'topic' => 'Duplikat',
            ])
            ->assertSessionHasErrors('schedule_id');
    }

    public function test_admin_only_manage_schedule(): void
    {
        $this->actingAs($this->guru)
            ->get(route('schedules.create'))
            ->assertForbidden();

        $this->actingAs($this->guru)
            ->get(route('schedules.index'))
            ->assertOk();
    }

    public function test_admin_cannot_fill_journal(): void
    {
        $this->actingAs($this->admin)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'date' => now()->toDateString(),
                'topic' => 'Test',
            ])
            ->assertForbidden();
    }

    public function test_guru_cannot_manage_journal_of_other_guru(): void
    {
        $guruB = User::factory()->guru($this->tenant->id)->create();
        $journal = Journal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $guruB->id,
            'rombel_id' => $this->rombel->id,
            'topic' => 'Jurnal Guru Lain',
        ]);

        $this->actingAs($this->guru)
            ->get(route('journals.show', $journal))
            ->assertForbidden();

        $this->actingAs($this->guru)
            ->get(route('journals.edit', $journal))
            ->assertForbidden();

        $this->actingAs($this->guru)
            ->put(route('journals.update', $journal), [
                'rombel_id' => $this->rombel->id,
                'date' => now()->toDateString(),
                'topic' => 'Dicuri',
            ])
            ->assertForbidden();
    }

    public function test_guru_store_rejects_schedule_of_other_guru(): void
    {
        $guruB = User::factory()->guru($this->tenant->id)->create();
        $teacherB = $this->makeTeacher($guruB, $this->subject);
        $scheduleB = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $teacherB->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 2,
        ]);

        $this->actingAs($this->guru)
            ->post(route('journals.store'), [
                'rombel_id' => $this->rombel->id,
                'schedule_id' => $scheduleB->id,
                'date' => now()->toDateString(),
                'topic' => 'Bukan Jadwal Saya',
            ])
            ->assertSessionHasErrors('schedule_id');
    }

    public function test_guru_schedule_index_only_shows_own_schedules(): void
    {
        $guruB = User::factory()->guru($this->tenant->id)->create();
        $subjectB = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Sejarah']);
        $teacherB = $this->makeTeacher($guruB, $subjectB);

        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 5,
        ]);
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $teacherB->id,
            'subject_id' => $subjectB->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 5,
        ]);

        $this->actingAs($this->guru)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Matematika')
            ->assertDontSee('Sejarah');
    }

    public function test_schedule_delete_blocked_when_has_journal(): void
    {
        $schedule = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 1,
        ]);

        Journal::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'rombel_id' => $this->rombel->id,
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('schedules.destroy', $schedule))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_schedule_delete_blocked_when_has_lesson_attendance(): void
    {
        $schedule = Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 2,
        ]);

        $student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
        ]);

        Attendance::factory()->lesson()->create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('schedules.destroy', $schedule))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }
}
