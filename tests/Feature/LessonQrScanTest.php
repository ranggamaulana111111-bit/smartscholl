<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonQrScanTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private User $guruA;

    private Rombel $rombelA;

    private Schedule $scheduleToday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'sman1.smartschool.id']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'smpharapan.smartschool.id']);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();

        $year = AcademicYear::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'is_active' => true,
            'name' => '2026/2027',
        ]);

        $this->rombelA = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
        ]);

        $this->guruA = User::factory()->guru($this->tenantA->id)->create();
        Teacher::factory()->withUser($this->guruA)->create(['tenant_id' => $this->tenantA->id]);

        $this->scheduleToday = $this->makeSchedule(
            $this->tenantA->id,
            $year->id,
            $this->guruA->id,
            $this->rombelA->id,
            now()->toDateString(),
        );
    }

    public function test_guest_is_redirected_from_lesson_scan_pages(): void
    {
        $this->get(route('attendance.schedule.scan.index', $this->scheduleToday->id))
            ->assertRedirect(route('login'));

        $this->get(route('attendance.schedule.scan.status', $this->scheduleToday->id))
            ->assertRedirect(route('login'));

        $this->post(route('attendance.schedule.scan.remaining', $this->scheduleToday->id), ['status' => 'izin'])
            ->assertRedirect(route('login'));
    }

    public function test_guru_can_open_scan_page_for_own_schedule(): void
    {
        Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $this->rombelA->id,
            'nisn' => '0039123456',
            'name' => 'Aisyah Putri',
        ]);

        $this->actingAs($this->guruA)
            ->get(route('attendance.schedule.scan.index', $this->scheduleToday->id))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertSee('0039123456')
            ->assertSee('data-scan-video', false)
            ->assertSee(route('attendance.schedule.scan.status', $this->scheduleToday->id), false);
    }

    public function test_guru_cannot_open_scan_page_for_other_teachers_schedule(): void
    {
        $otherGuru = User::factory()->guru($this->tenantA->id)->create();
        Teacher::factory()->withUser($otherGuru)->create(['tenant_id' => $this->tenantA->id]);

        $foreign = $this->makeSchedule(
            $this->tenantA->id,
            null,
            $otherGuru->id,
            $this->rombelA->id,
            now()->toDateString(),
        );

        $this->actingAs($this->guruA)
            ->get(route('attendance.schedule.scan.index', $foreign->id))
            ->assertForbidden();

        $this->actingAs($this->guruA)
            ->get(route('attendance.schedule.scan.status', $foreign->id))
            ->assertForbidden();
    }

    public function test_scan_page_of_other_tenant_is_not_found(): void
    {
        $foreign = $this->makeSchedule(
            $this->tenantB->id,
            null,
            null,
            null,
            now()->toDateString(),
        );

        $this->actingAs($this->adminA)
            ->get(route('attendance.schedule.scan.index', $foreign->id))
            ->assertNotFound();
    }

    public function test_status_endpoint_returns_roster_with_counts(): void
    {
        $scanned = $this->studentForRombel('0039123456', 'Aisyah Putri');
        $unscanned = $this->studentForRombel('0039123457', 'Bagas Wicrama');

        $this->actingAs($this->guruA)
            ->postJson(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $this->scheduleToday->id,
            ])
            ->assertOk();

        $response = $this->actingAs($this->guruA)
            ->getJson(route('attendance.schedule.scan.status', $this->scheduleToday->id))
            ->assertOk()
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.recorded', 1)
            ->assertJsonPath('summary.remaining', 1)
            ->assertJsonPath('summary.hadir', 1);

        $rows = collect($response->json('roster'))->keyBy('id');

        $this->assertSame('hadir', $rows[$scanned->id]['status']);
        $this->assertNull($rows[$unscanned->id]['status']);
    }

    public function test_mark_remaining_records_only_unrecorded_students(): void
    {
        $scanned = $this->studentForRombel('0039123456', 'Aisyah Putri');
        $sick = $this->studentForRombel('0039123457', 'Bagas Wicrama');
        $absent = $this->studentForRombel('0039123458', 'Citra Lestari');

        $this->actingAs($this->guruA)
            ->postJson(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $this->scheduleToday->id,
            ])
            ->assertOk();

        $this->actingAs($this->guruA)
            ->post(route('attendance.schedule.scan.remaining', $this->scheduleToday->id), [
                'status' => 'sakit',
                'note' => 'Surat dokter',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $sick->id,
            'schedule_id' => $this->scheduleToday->id,
            'type' => 'lesson',
            'status' => 'sakit',
            'source' => 'manual',
            'note' => 'Surat dokter',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $absent->id,
            'schedule_id' => $this->scheduleToday->id,
            'status' => 'sakit',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $scanned->id,
            'schedule_id' => $this->scheduleToday->id,
            'status' => 'hadir',
            'source' => 'scan',
        ]);

        $this->assertDatabaseCount('attendances', 3);
    }

    public function test_mark_remaining_keeps_already_scanned_rows_untouched(): void
    {
        $this->studentForRombel('0039123456', 'Aisyah Putri');
        $pending = $this->studentForRombel('0039123457', 'Bagas Wicrama');

        $this->actingAs($this->guruA)
            ->postJson(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $this->scheduleToday->id,
            ])
            ->assertOk();

        $this->actingAs($this->guruA)
            ->post(route('attendance.schedule.scan.remaining', $this->scheduleToday->id), ['status' => 'izin'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 2);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $pending->id,
            'status' => 'izin',
        ]);

        $this->assertSame(
            1,
            Attendance::where('schedule_id', $this->scheduleToday->id)->where('status', 'hadir')->count()
        );
    }

    public function test_mark_remaining_rejects_hadir_status(): void
    {
        $this->studentForRombel('0039123456', 'Aisyah Putri');

        $this->actingAs($this->guruA)
            ->from(route('attendance.schedule.scan.index', $this->scheduleToday->id))
            ->post(route('attendance.schedule.scan.remaining', $this->scheduleToday->id), ['status' => 'hadir'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_mark_remaining_reports_when_nothing_is_pending(): void
    {
        $this->studentForRombel('0039123456', 'Aisyah Putri');

        $this->actingAs($this->guruA)
            ->postJson(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $this->scheduleToday->id,
            ])
            ->assertOk();

        $this->actingAs($this->guruA)
            ->post(route('attendance.schedule.scan.remaining', $this->scheduleToday->id), ['status' => 'alpha'])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_mark_remaining_is_blocked_outside_schedule_time(): void
    {
        $later = $this->makeSchedule(
            $this->tenantA->id,
            null,
            $this->guruA->id,
            $this->rombelA->id,
            now()->toDateString(),
            Carbon::now()->addHours(2)->format('H:i:s'),
            Carbon::now()->addHours(3)->format('H:i:s'),
        );

        $this->studentForRombel('0039123456', 'Aisyah Putri');

        $this->actingAs($this->guruA)
            ->post(route('attendance.schedule.scan.remaining', $later->id), ['status' => 'izin'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_scan_page_warns_when_outside_schedule_time(): void
    {
        $later = $this->makeSchedule(
            $this->tenantA->id,
            null,
            $this->guruA->id,
            $this->rombelA->id,
            now()->toDateString(),
            Carbon::now()->addHours(2)->format('H:i:s'),
            Carbon::now()->addHours(3)->format('H:i:s'),
        );

        $this->studentForRombel('0039123456', 'Aisyah Putri');

        $this->actingAs($this->guruA)
            ->get(route('attendance.schedule.scan.index', $later->id))
            ->assertOk()
            ->assertSee('Di luar jam pelajaran');
    }

    public function test_scan_page_shows_empty_state_for_rombel_without_students(): void
    {
        $this->actingAs($this->guruA)
            ->get(route('attendance.schedule.scan.index', $this->scheduleToday->id))
            ->assertOk()
            ->assertSee('Rombel ini belum punya siswa');
    }

    public function test_guru_dashboard_links_to_scan_page(): void
    {
        $this->actingAs($this->guruA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('attendance.schedule.scan.index', $this->scheduleToday->id), false);
    }

    public function test_guru_sees_qr_cards_of_own_classes_only(): void
    {
        $mine = $this->studentForRombel('0039123456', 'Aisyah Putri');

        $foreignRombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->rombelA->academic_year_id,
            'name' => 'Z-9',
        ]);

        $foreign = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $foreignRombel->id,
            'nisn' => '0039123459',
            'name' => 'Zaky Pratama',
        ]);

        $this->actingAs($this->guruA)
            ->get(route('attendance.qrcodes'))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertDontSee('Zaky Pratama');

        $this->actingAs($this->guruA)
            ->get(route('attendance.qrcode', $mine))
            ->assertOk()
            ->assertSee('Aisyah Putri');

        $this->actingAs($this->guruA)
            ->get(route('attendance.qrcode', $foreign))
            ->assertForbidden();
    }

    public function test_guru_sees_qr_cards_of_taught_class_without_being_homeroom(): void
    {
        $rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->rombelA->academic_year_id,
            'name' => 'VIII-B',
        ]);

        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombel->id,
            'nisn' => '0039123460',
            'name' => 'Nadia Larasati',
        ]);

        $this->makeSchedule(
            $this->tenantA->id,
            null,
            $this->guruA->id,
            $rombel->id,
            now()->toDateString(),
        );

        $this->actingAs($this->guruA)
            ->get(route('attendance.qrcodes', ['rombel' => $rombel->id]))
            ->assertOk()
            ->assertSee('Nadia Larasati');

        $this->actingAs($this->guruA)
            ->get(route('attendance.qrcode', $student))
            ->assertOk();
    }

    public function test_admin_still_sees_every_qr_card(): void
    {
        $this->studentForRombel('0039123456', 'Aisyah Putri');

        $foreignRombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->rombelA->academic_year_id,
            'name' => 'X-7',
        ]);

        Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $foreignRombel->id,
            'nisn' => '0039123461',
            'name' => 'Wahyu Nugroho',
        ]);

        $this->actingAs($this->adminA)
            ->get(route('attendance.qrcodes'))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertSee('Wahyu Nugroho');
    }

    private function studentForRombel(string $nisn, string $name): Student
    {
        return Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $this->rombelA->id,
            'nisn' => $nisn,
            'name' => $name,
        ]);
    }

    private function makeSchedule(
        string $tenantId,
        ?int $yearId,
        ?int $userId,
        ?int $rombelId,
        string $date,
        ?string $start = null,
        ?string $end = null,
    ): Schedule {
        $subject = Subject::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'Mapel '.fake()->unique()->bothify('##'),
        ]);

        $user = $userId
            ? User::query()->findOrFail($userId)
            : User::factory()->guru($tenantId)->create();

        $teacher = Teacher::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->first()
            ?? Teacher::factory()->withUser($user)->create([
                'tenant_id' => $tenantId,
                'subject_id' => $subject->id,
            ]);

        if ($rombelId === null) {
            $rombelId = Rombel::factory()->create([
                'tenant_id' => $tenantId,
                'academic_year_id' => $yearId ?? AcademicYear::factory()->create(['tenant_id' => $tenantId])->id,
            ])->id;
        }

        return Schedule::factory()->create([
            'tenant_id' => $tenantId,
            'academic_year_id' => $yearId ?? AcademicYear::factory()->create([
                'tenant_id' => $tenantId,
                'is_active' => true,
            ])->id,
            'teacher_id' => $teacher->id,
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombelId,
            'day_of_week' => (int) Carbon::parse($date)->isoWeekday(),
            'start_time' => $start ?? Carbon::now()->subMinutes(30)->format('H:i:s'),
            'end_time' => $end ?? Carbon::now()->addMinutes(30)->format('H:i:s'),
        ]);
    }
}
