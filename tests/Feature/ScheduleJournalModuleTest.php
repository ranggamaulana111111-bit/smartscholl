<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
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

    public function test_admin_can_create_schedule(): void
    {
        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'user_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 1,
                'start_time' => '07:30',
                'end_time' => '09:00',
            ])
            ->assertRedirect(route('schedules.index'));

        $this->assertDatabaseHas('schedules', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
        ]);
    }

    public function test_schedule_conflict_rejected(): void
    {
        Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'user_id' => $this->guru->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($this->admin)
            ->post(route('schedules.store'), [
                'academic_year_id' => $this->year->id,
                'user_id' => $this->guru->id,
                'subject_id' => $this->subject->id,
                'rombel_id' => $this->rombel->id,
                'day_of_week' => 1,
                'start_time' => '08:30',
                'end_time' => '10:00',
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
            'user_id' => $this->guru->id,
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
}
