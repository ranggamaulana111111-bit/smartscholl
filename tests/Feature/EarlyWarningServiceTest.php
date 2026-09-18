<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Attendance;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\Subject;
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
        $cursor = now()->startOfDay();

        while ($cursor->isWeekend()) {
            $cursor->subDay();
        }

        $days = [];

        for ($i = 0; $i < 3; $i++) {
            $days[] = $cursor->copy()->subDays($i)->toDateString();
        }

        foreach ($days as $date) {
            Attendance::factory()->alpha()->recordedAt($date, '08:00:00')->create([
                'tenant_id' => $this->tenant->id,
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
}
