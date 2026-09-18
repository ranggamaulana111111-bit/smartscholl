<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private Student $studentA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'sman1.smartschool.id']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'smpharapan.smartschool.id']);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();

        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true, 'name' => '2026/2027']);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $this->studentA = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombel->id,
            'nisn' => '0039123456',
        ]);
    }

    public function test_guest_cannot_access_attendance_pages(): void
    {
        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->get(route('attendance.scan'))->assertRedirect(route('login'));
        $this->post(route('attendance.record'))->assertRedirect(route('login'));
    }

    public function test_scan_valid_payload_records_gate_in_once(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123456'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'type' => 'gate_in',
            'status' => 'hadir',
            'source' => 'scan',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123456'])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_scan_qr_token_records_attendance(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => $this->studentA->qrcodePayload()])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'type' => 'gate_in',
            'source' => 'scan',
        ]);
    }

    public function test_scan_rfid_uid_records_attendance(): void
    {
        $this->studentA->update(['rfid_uid' => '04A2B3C4D5']);

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => '04a2b3c4d5'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'type' => 'gate_in',
            'source' => 'scan',
        ]);
    }

    public function test_scan_accepts_json_requests(): void
    {
        $this->actingAs($this->adminA)
            ->postJson(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123456'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->actingAs($this->adminA)
            ->postJson(route('attendance.record'), ['payload' => 'UNKNOWN-CODE'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_bulk_scan_records_multiple_payloads(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $lain = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombel->id,
            'nisn' => '0039123400',
            'rfid_uid' => '04A2B3C4D5',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('attendance.recordBulk', ['mode' => 'gate_in']), [
                'payloads' => "SS:0039123456\n04a2b3c4d5",
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', ['student_id' => $lain->id, 'type' => 'gate_in']);
    }

    public function test_bulk_scan_reports_rejected_codes(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.recordBulk'), [
                'payloads' => "SS:0039123456\nTIDAK-ADA",
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_gate_out_without_gate_in_is_flagged(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_out']), ['payload' => 'SS:0039123456'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'type' => 'gate_out',
            'note' => 'Pulang tanpa catatan masuk hari ini',
        ]);
    }

    public function test_guru_can_manual_record_only_homeroom_students(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id]);

        $homeroom = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
            'name' => 'X-1',
            'homeroom_teacher_id' => $guru->id,
        ]);
        $outside = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
            'name' => 'X-2',
        ]);

        $binaan = Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $homeroom->id, 'nisn' => '0039123401']);
        Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $outside->id, 'nisn' => '0039123402', 'name' => 'Siswa Luar Binaan']);

        $schedule = $this->makeSchedule($this->tenantA->id, $year->id, $guru->id, $homeroom->id, '2026-09-18');

        $this->actingAs($guru)
            ->get(route('attendance.manual'))
            ->assertOk()
            ->assertSee($binaan->name)
            ->assertDontSee('Siswa Luar Binaan');

        $this->actingAs($guru)
            ->post(route('attendance.storeManual'), [
                'student_id' => $binaan->id,
                'schedule_id' => $schedule->id,
                'type' => 'lesson',
                'status' => 'sakit',
                'date' => '2026-09-18',
            ])
            ->assertSessionHas('success');

        $this->actingAs($guru)
            ->post(route('attendance.storeManual'), [
                'student_id' => Student::where('nisn', '0039123402')->firstOrFail()->id,
                'type' => 'lesson',
                'status' => 'hadir',
                'date' => '2026-09-18',
            ])
            ->assertForbidden();
    }

    public function test_invalid_payload_is_rejected(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record'), ['payload' => 'SOMETHING'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_nisn_of_other_tenant_is_not_found(): void
    {
        $yearB = AcademicYear::factory()->create(['tenant_id' => $this->tenantB->id]);
        $rombelB = Rombel::factory()->create(['tenant_id' => $this->tenantB->id, 'academic_year_id' => $yearB->id]);
        Student::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'rombel_id' => $rombelB->id,
            'nisn' => '0039999999',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('attendance.record'), ['payload' => 'SS:0039999999'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_manual_attendance_is_saved(): void
    {
        $schedule = $this->makeSchedule($this->tenantA->id, $this->studentA->academic_year_id, null, $this->studentA->rombel_id, '2026-09-15');

        $this->actingAs($this->adminA)
            ->post(route('attendance.storeManual'), [
                'student_id' => $this->studentA->id,
                'schedule_id' => $schedule->id,
                'type' => 'lesson',
                'status' => 'sakit',
                'date' => '2026-09-15',
                'note' => 'Demam',
            ])
            ->assertRedirect(route('attendance.manual'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'schedule_id' => $schedule->id,
            'type' => 'lesson',
            'status' => 'sakit',
            'date' => '2026-09-15',
            'source' => 'manual',
            'note' => 'Demam',
        ]);
    }

    public function test_manual_lesson_updates_existing_for_same_schedule(): void
    {
        $schedule = $this->makeSchedule($this->tenantA->id, $this->studentA->academic_year_id, null, $this->studentA->rombel_id, '2026-09-15');

        Attendance::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'schedule_id' => $schedule->id,
            'student_id' => $this->studentA->id,
            'type' => 'lesson',
            'date' => '2026-09-15',
            'source' => 'scan',
            'status' => 'hadir',
        ]);

        $this->actingAs($this->adminA)
            ->post(route('attendance.storeManual'), [
                'student_id' => $this->studentA->id,
                'schedule_id' => $schedule->id,
                'type' => 'lesson',
                'status' => 'sakit',
                'date' => '2026-09-15',
                'note' => 'Koreksi',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'schedule_id' => $schedule->id,
            'type' => 'lesson',
            'status' => 'sakit',
            'source' => 'manual',
            'note' => 'Koreksi',
            'date' => '2026-09-15',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'entity_type' => 'Attendance',
            'entity_id' => Attendance::where('student_id', $this->studentA->id)->firstOrFail()->id,
        ]);
    }

    public function test_manual_lesson_records_separate_rows_per_schedule(): void
    {
        $first = $this->makeSchedule($this->tenantA->id, null, null, $this->studentA->rombel_id, '2026-09-15');
        $second = $this->makeSchedule($this->tenantA->id, null, null, $this->studentA->rombel_id, '2026-09-15');

        foreach ([$first, $second] as $schedule) {
            $this->actingAs($this->adminA)
                ->post(route('attendance.storeManual'), [
                    'student_id' => $this->studentA->id,
                    'schedule_id' => $schedule->id,
                    'type' => 'lesson',
                    'status' => 'hadir',
                    'date' => '2026-09-15',
                ])
                ->assertSessionHas('success');
        }

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', ['schedule_id' => $first->id, 'type' => 'lesson']);
        $this->assertDatabaseHas('attendances', ['schedule_id' => $second->id, 'type' => 'lesson']);
    }

    public function test_gate_in_and_gate_out_separate_entries(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => '0039123456'])
            ->assertSessionHas('success');

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_out']), ['payload' => '0039123456'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_index_lists_attendance_for_user_role(): void
    {
        Attendance::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'student_id' => $this->studentA->id,
            'type' => 'gate_in',
            'date' => today()->toDateString(),
        ]);

        $this->actingAs($this->adminA)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee($this->studentA->nisn);
    }

    public function test_qrcodes_page_renders_qr(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('attendance.qrcodes'))
            ->assertOk()
            ->assertSee('0039123456')
            ->assertSee('<svg', false);
    }

    public function test_lesson_scan_requires_own_schedule_for_guru(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $otherGuru = User::factory()->guru($this->tenantA->id)->create();

        $own = $this->makeSchedule($this->tenantA->id, null, $guru->id, $this->studentA->rombel_id, now()->toDateString());
        $foreign = $this->makeSchedule($this->tenantA->id, null, $otherGuru->id, $this->studentA->rombel_id, now()->toDateString());

        $this->actingAs($guru)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $own->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'type' => 'lesson',
            'schedule_id' => $own->id,
        ]);

        $this->actingAs($guru)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $foreign->id,
            ])
            ->assertSessionHas('error');
    }

    public function test_lesson_scan_rejects_wrong_rombel(): void
    {
        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true]);
        $rombelLain = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        Student::factory()->create(['tenant_id' => $this->tenantA->id, 'rombel_id' => $rombelLain->id, 'nisn' => '0039123457']);
        $guru = User::factory()->guru($this->tenantA->id)->create();

        $schedule = $this->makeSchedule($this->tenantA->id, $year->id, $guru->id, $rombelLain->id, now()->toDateString());

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('error');

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123457',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');
    }

    public function test_lesson_scan_records_multiple_schedules_same_day(): void
    {
        $first = $this->makeSchedule($this->tenantA->id, null, null, $this->studentA->rombel_id, now()->toDateString());
        $second = $this->makeSchedule($this->tenantA->id, null, null, $this->studentA->rombel_id, now()->toDateString());

        foreach ([$first, $second] as $schedule) {
            $this->actingAs($this->adminA)
                ->post(route('attendance.record', ['mode' => 'lesson']), [
                    'payload' => 'SS:0039123456',
                    'schedule_id' => $schedule->id,
                ])
                ->assertSessionHas('success');
        }

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseHas('attendances', ['schedule_id' => $first->id, 'student_id' => $this->studentA->id]);
        $this->assertDatabaseHas('attendances', ['schedule_id' => $second->id, 'student_id' => $this->studentA->id]);

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $first->id,
            ])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_lesson_scan_rejects_outside_schedule_time(): void
    {
        $schedule = $this->makeSchedule(
            $this->tenantA->id,
            null,
            null,
            $this->studentA->rombel_id,
            now()->toDateString(),
            Carbon::now()->addHours(2)->format('H:i:s'),
            Carbon::now()->addHours(3)->format('H:i:s'),
        );

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_lesson_scan_accepts_within_tolerance_before_start(): void
    {
        $schedule = $this->makeSchedule(
            $this->tenantA->id,
            null,
            null,
            $this->studentA->rombel_id,
            now()->toDateString(),
            Carbon::now()->addMinutes(10)->format('H:i:s'),
            Carbon::now()->addHours(1)->format('H:i:s'),
        );

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', ['schedule_id' => $schedule->id, 'type' => 'lesson']);
    }

    public function test_scan_create_writes_audit_log(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123456'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity_type' => 'Attendance',
            'user_id' => $this->adminA->id,
        ]);
    }

    public function test_lesson_attendance_uses_schedule_academic_year(): void
    {
        $otherYear = AcademicYear::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'is_active' => false,
        ]);

        $schedule = $this->makeSchedule($this->tenantA->id, $otherYear->id, null, $this->studentA->rombel_id, now()->toDateString());

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'schedule_id' => $schedule->id,
            'academic_year_id' => $otherYear->id,
        ]);
    }

    public function test_manual_lesson_rejects_day_mismatch_with_schedule(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $schedule = $this->makeSchedule($this->tenantA->id, null, $guru->id, $this->studentA->rombel_id, now()->toDateString());

        $wrongDay = Carbon::parse(now()->toDateString())->addDay()->toDateString();

        $this->actingAs($this->adminA)
            ->post(route('attendance.storeManual'), [
                'student_id' => $this->studentA->id,
                'schedule_id' => $schedule->id,
                'type' => 'lesson',
                'status' => 'hadir',
                'date' => $wrongDay,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    private function makeSchedule(string $tenantId, ?int $yearId, ?int $userId, int $rombelId, string $date, ?string $start = null, ?string $end = null): Schedule
    {
        $subject = Subject::factory()->create(['tenant_id' => $tenantId, 'name' => 'Mapel '.fake()->unique()->bothify('##')]);

        return Schedule::factory()->create([
            'tenant_id' => $tenantId,
            'academic_year_id' => $yearId ?? AcademicYear::where('is_active', true)->value('id'),
            'user_id' => $userId ?? User::factory()->guru($tenantId)->create()->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombelId,
            'day_of_week' => (int) Carbon::parse($date)->isoWeekday(),
            'start_time' => $start ?? Carbon::now()->subMinutes(30)->format('H:i:s'),
            'end_time' => $end ?? Carbon::now()->addMinutes(30)->format('H:i:s'),
        ]);
    }
}
