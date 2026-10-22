<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\EarlyWarningLog;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EarlyWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private Rombel $rombel;

    private AcademicYear $year;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['domain' => 'sma.test.id']);
        $this->admin = User::factory()->adminSekolah($this->tenant->id)->create();
        $this->year = AcademicYear::factory()->active()->create(['tenant_id' => $this->tenant->id]);
        $this->rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-1',
        ]);
        $this->student = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039111222',
        ]);
    }

    public function test_absence_streak_trigger(): void
    {
        foreach ($this->schoolDaysEndingToday(3) as $date) {
            Attendance::factory()->alpha()->recordedAt($date, '08:00:00')->create([
                'tenant_id' => $this->tenant->id,
                'academic_year_id' => $this->year->id,
                'student_id' => $this->student->id,
                'type' => 'lesson',
            ]);
        }

        $service = new EarlyWarningService;
        $logs = $service->runCheck($this->tenant->id);

        $this->assertTrue(
            $logs->contains(fn ($log) => $log->type === 'absence_streak')
        );
    }

    public function test_low_score_trigger(): void
    {
        $subject = Subject::factory()->create(['tenant_id' => $this->tenant->id]);
        $assessment = Assessment::factory()->formatif()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $subject->id,
            'rombel_id' => $this->rombel->id,
        ]);
        AssessmentGrade::factory()->belowKkm()->create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'student_id' => $this->student->id,
            'score' => 55,
        ]);

        $service = new EarlyWarningService;
        $logs = $service->runCheck($this->tenant->id);

        $this->assertTrue(
            $logs->contains(fn ($log) => $log->type === 'low_score')
        );
    }

    public function test_no_warning_for_healthy_student(): void
    {
        Attendance::factory()->lesson()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'student_id' => $this->student->id,
            'status' => 'hadir',
        ]);

        $service = new EarlyWarningService;
        $logs = $service->runCheck($this->tenant->id);

        $this->assertTrue($logs->isEmpty());
    }

    public function test_admin_can_access_ews_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ews.index'))
            ->assertOk();
    }

    public function test_student_cannot_access_ews_management(): void
    {
        $this->actingAs($this->student->user ?? User::factory()->siswa($this->tenant->id)->create())
            ->get(route('ews.index'))
            ->assertForbidden();
    }

    public function test_guru_ews_index_only_shows_homeroom_students(): void
    {
        $guru = User::factory()->guru($this->tenant->id)->create();
        $teacher = Teacher::factory()->withUser($guru)->create(['tenant_id' => $this->tenant->id]);
        $this->rombel->update(['homeroom_teacher_id' => $teacher->id]);

        EarlyWarningLog::create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $this->student->id,
            'type' => 'absence_streak',
            'description' => 'Binaan saya.',
            'trigger_date' => now()->toDateString(),
        ]);

        $otherGuru = User::factory()->guru($this->tenant->id)->create();
        $otherTeacher = Teacher::factory()->withUser($otherGuru)->create(['tenant_id' => $this->tenant->id]);
        $rombelB = Rombel::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-2',
            'homeroom_teacher_id' => $otherTeacher->id,
        ]);
        $studentB = Student::factory()->create([
            'tenant_id' => $this->tenant->id,
            'rombel_id' => $rombelB->id,
        ]);
        EarlyWarningLog::create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $studentB->id,
            'type' => 'absence_streak',
            'description' => 'Bukan binaan.',
            'trigger_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($guru)
            ->get(route('ews.index'))
            ->assertOk();

        $logs = $response->viewData('logs');

        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->every(fn ($log) => $log->student->rombel->homeroom_teacher_id === $teacher->id));
    }

    /**
     * Tanggal hari sekolah berturut-turut, berakhir hari ini.
     *
     * Menghitung satu per satu lewat kalender, bukan `subDays($i)`, supaya
     * deret yang dibentuk tidak pernah melewati akhir pekan. Kalau tidak,
     * test hanya lulus pada hari tertentu dalam seminggu.
     *
     * @return array<int, string>
     */
    private function schoolDaysEndingToday(int $count): array
    {
        $days = [];
        $cursor = now()->startOfDay();

        while (count($days) < $count) {
            if ($cursor->isWeekend()) {
                $cursor->subDay();

                continue;
            }

            $days[] = $cursor->toDateString();
            $cursor->subDay();
        }

        return $days;
    }

    private function seedAlphaStreak(): void
    {
        foreach ($this->schoolDaysEndingToday(3) as $date) {
            Attendance::factory()->alpha()->recordedAt($date, '08:00:00')->create([
                'tenant_id' => $this->tenant->id,
                'academic_year_id' => $this->year->id,
                'student_id' => $this->student->id,
                'type' => 'lesson',
            ]);
        }
    }

    public function test_run_check_does_not_duplicate_unresolved_warnings(): void
    {
        $this->seedAlphaStreak();

        $service = new EarlyWarningService;
        $service->runCheck($this->tenant->id);
        $service->runCheck($this->tenant->id);
        $service->runCheck($this->tenant->id);

        $this->assertSame(
            1,
            EarlyWarningLog::where('student_id', $this->student->id)->where('type', 'absence_streak')->where('is_resolved', false)->count()
        );
        $this->assertSame(2, EarlyWarningLog::count());
    }

    public function test_resolved_warning_from_previous_day_allows_new_warning(): void
    {
        $this->seedAlphaStreak();

        EarlyWarningLog::create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $this->student->id,
            'type' => 'absence_streak',
            'description' => 'Peringatan lama (sudah selesai).',
            'trigger_date' => now()->subDay()->toDateString(),
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        $service = new EarlyWarningService;
        $service->runCheck($this->tenant->id);

        $this->assertSame(
            2,
            EarlyWarningLog::where('student_id', $this->student->id)->where('type', 'absence_streak')->count()
        );
        $this->assertSame(
            1,
            EarlyWarningLog::where('student_id', $this->student->id)->where('type', 'absence_streak')->where('is_resolved', false)->count()
        );
    }
}
