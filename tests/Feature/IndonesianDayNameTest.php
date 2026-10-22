<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndonesianDayNameTest extends TestCase
{
    use RefreshDatabase;

    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    private Tenant $tenant;

    private User $admin;

    private Subject $subject;

    private Rombel $rombel;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'sma.test.id']);
        $this->admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $this->subject = Subject::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Matematika']);
        $this->rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => AcademicYear::factory()->active()->create(['tenant_id' => $this->tenant->id])->id,
            'name' => 'X-1',
        ]);
        $this->teacher = Teacher::factory()->withUser(User::factory()->guru($this->tenant->id)->create())->create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    private function makeSchedule(int $dayOfWeek): Schedule
    {
        return Schedule::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->rombel->academic_year_id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => $dayOfWeek,
        ]);
    }

    public function test_schedule_index_shows_indonesian_day_name(): void
    {
        $this->makeSchedule(2);

        $this->actingAs($this->admin)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Selasa')
            ->assertDontSee('Tuesday');
    }

    public function test_schedule_create_offers_all_indonesian_day_names(): void
    {
        $response = $this->actingAs($this->admin)->get(route('schedules.create'));

        $response->assertOk();

        foreach (self::HARI as $hari) {
            $response->assertSee($hari);
        }

        $response->assertDontSee('Monday');
        $response->assertDontSee('Sunday');
    }

    public function test_schedule_edit_keeps_selected_day_name_indonesian(): void
    {
        $schedule = $this->makeSchedule(4);

        $this->actingAs($this->admin)
            ->get(route('schedules.edit', $schedule))
            ->assertOk()
            ->assertSee('Kamis')
            ->assertDontSee('Thursday');
    }

    public function test_teacher_show_lists_indonesian_day_name(): void
    {
        $this->makeSchedule(3);

        $this->actingAs($this->admin)
            ->get(route('teachers.show', $this->teacher))
            ->assertOk()
            ->assertSee('Rabu')
            ->assertDontSee('Wednesday');
    }

    public function test_topbar_shows_indonesian_day_name_for_today(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk()->assertSee(self::HARI[now()->dayOfWeekIso - 1]);
    }
}
