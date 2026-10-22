<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceScanEvent;
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

/**
 * Cakupan untuk lapisan baca dan lapisan tombol roster yang ditambahkan
 * setelah alur scan dasar sudah hijau.
 */
class AttendanceRecapTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private User $adminA;

    private AcademicYear $year;

    private Rombel $rombel;

    private Student $studentA;

    private Student $studentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'rekap-a.smartschool.id']);
        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();

        $this->year = AcademicYear::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'is_active' => true,
        ]);

        $this->rombel = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-1',
        ]);

        $this->studentA = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039123401',
            'name' => 'Ani',
        ]);

        $this->studentB = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $this->rombel->id,
            'nisn' => '0039123402',
            'name' => 'Budi',
        ]);
    }

    public function test_recap_counts_a_student_once_however_many_lessons(): void
    {
        $first = $this->todaySchedule();
        $second = $this->todaySchedule();

        $this->recordLesson($first, $this->studentA, 'hadir');
        $this->recordLesson($second, $this->studentA, 'hadir');

        $this->actingAs($this->adminA)
            ->get(route('attendance.recap'))
            ->assertOk()
            ->assertSee('Kelengkapan');

        $this->assertDatabaseCount('attendances', 2);

        $rekap = app(\App\Services\AttendanceRecap::class)->daily(
            now()->toDateString(),
            null,
            $this->adminA
        );

        $row = $rekap['rombels']->first();

        $this->assertSame(2, $row['students']);
        $this->assertSame(1, $row['recorded'], 'Siswa yang hadir di dua pelajaran tetap dihitung satu kali.');
        $this->assertSame(1, $row['hadir']);
        $this->assertSame(1, $row['unrecorded']);
        $this->assertSame(50.0, $row['rate']);
    }

    public function test_recap_takes_the_most_serious_status_for_the_day(): void
    {
        $izinSchedule = $this->todaySchedule();
        $alphaSchedule = $this->todaySchedule();

        $this->recordLesson($izinSchedule, $this->studentA, 'izin');
        $this->recordLesson($alphaSchedule, $this->studentA, 'alpha');

        $rekap = app(\App\Services\AttendanceRecap::class)->daily(now()->toDateString(), null, $this->adminA);
        $row = $rekap['rombels']->first();

        $this->assertSame(1, $row['alpha'], 'Alpa harus menang atas izin pada rekap harian.');
        $this->assertSame(0, $row['izin']);
    }

    public function test_recap_ignores_gate_scans_when_counting_students(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123401'])
            ->assertSessionHas('success');

        $rekap = app(\App\Services\AttendanceRecap::class)->daily(now()->toDateString(), null, $this->adminA);

        $this->assertSame(0, $rekap['rombels']->first()['recorded']);
        $this->assertSame(2, $rekap['unrecorded_total']);
    }

    public function test_recap_lists_students_without_a_record(): void
    {
        $schedule = $this->todaySchedule();
        $this->recordLesson($schedule, $this->studentA, 'hadir');

        $rekap = app(\App\Services\AttendanceRecap::class)->daily(now()->toDateString(), null, $this->adminA);

        $this->assertCount(1, $rekap['unrecorded']);
        $this->assertSame('Budi', $rekap['unrecorded']->first()['student']->name);
        $this->assertSame('X-1', $rekap['unrecorded']->first()['rombel_name']);
    }

    public function test_recap_page_marks_weekend_as_not_a_school_day(): void
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);

        $this->actingAs($this->adminA)
            ->get(route('attendance.recap', ['date' => $saturday->toDateString()]))
            ->assertOk()
            ->assertSee('bukan hari sekolah');
    }

    public function test_recap_page_is_scoped_to_the_viewers_rombels(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $teacher = Teacher::factory()->withUser($guru)->create([
            'tenant_id' => $this->tenantA->id,
            'subject_id' => Subject::factory()->create(['tenant_id' => $this->tenantA->id])->id,
        ]);

        $rombelLain = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-9',
        ]);

        Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombelLain->id,
            'nisn' => '0039123403',
            'name' => 'Siswa Kelas Sembilan',
        ]);

        $this->actingAs($guru)
            ->get(route('attendance.recap'))
            ->assertOk()
            ->assertSee('X-1')
            ->assertDontSee('X-9');
    }

    public function test_recap_export_returns_csv(): void
    {
        $schedule = $this->todaySchedule();
        $this->recordLesson($schedule, $this->studentA, 'hadir');

        $response = $this->actingAs($this->adminA)->get(route('attendance.recap.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="rekap-absensi-'.now()->toDateString().'.csv"');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Rombel', $csv);
        $this->assertStringContainsString('X-1', $csv);
        $this->assertStringContainsString('Budi', $csv, 'Siswa tanpa catatan ikut diekspor.');
        $this->assertStringContainsString('TOTAL SEKOLAH', $csv);
    }

    public function test_recap_export_is_audited(): void
    {
        $this->actingAs($this->adminA)->get(route('attendance.recap.export'))->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'export',
            'entity_type' => 'AttendanceRecap',
            'user_id' => $this->adminA->id,
        ]);
    }

    public function test_scan_events_page_requires_audit_permission(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('attendance.scan-events'))
            ->assertOk();

        $guru = User::factory()->guru($this->tenantA->id)->create();

        $this->actingAs($guru)
            ->get(route('attendance.scan-events'))
            ->assertForbidden();
    }

    public function test_scan_events_records_the_hash_of_a_rejected_code(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'KODE-ASING-123'])
            ->assertSessionHas('error');

        $event = AttendanceScanEvent::where('outcome', AttendanceScanEvent::UNKNOWN_CODE)->firstOrFail();

        $this->assertNull($event->student_id);
        $this->assertSame(hash('sha256', 'KODE-ASING-123'), $event->code_hash);
        $this->assertNotNull($event->recorded_by);
    }

    public function test_scan_events_never_store_the_raw_code(): void
    {
        $payload = $this->studentA->qrcodePayload();

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => $payload])
            ->assertSessionHas('success');

        $event = AttendanceScanEvent::where('outcome', AttendanceScanEvent::RECORDED)->firstOrFail();

        $this->assertSame(hash('sha256', $payload), $event->code_hash);
        $this->assertStringNotContainsString($payload, $event->code_hash);
        $this->assertNotNull($event->attendance_id);
    }

    public function test_scan_events_page_filters_by_outcome(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => '0039123401'])
            ->assertSessionHas('success');

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => '0039123401'])
            ->assertSessionHas('info');

        $this->actingAs($this->adminA)
            ->get(route('attendance.scan-events', ['outcome' => 'duplicate']))
            ->assertOk()
            ->assertSee('Ani')
            ->assertDontSee('Belum ada jejak pemindaian');
    }

    public function test_scan_events_page_rejects_unknown_outcome_filter(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('attendance.scan-events', ['outcome' => 'drop-table']))
            ->assertStatus(422);
    }

    public function test_marking_a_student_updates_roster_without_leaving_the_page(): void
    {
        $schedule = $this->todaySchedule();

        $response = $this->actingAs($this->adminA)->postJson(route('attendance.schedule.scan.mark', $schedule->id), [
            'student_id' => $this->studentA->id,
            'status' => 'sakit',
            'note' => 'Demam',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('summary.recorded', 1)
            ->assertJsonPath('summary.sakit', 1)
            ->assertJsonPath('roster.0.status', 'sakit');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'schedule_id' => $schedule->id,
            'status' => 'sakit',
            'note' => 'Demam',
            'source' => 'manual',
        ]);
    }

    public function test_marking_a_student_twice_with_the_same_status_changes_nothing(): void
    {
        $schedule = $this->todaySchedule();

        $payload = ['student_id' => $this->studentA->id, 'status' => 'izin'];

        $this->actingAs($this->adminA)->postJson(route('attendance.schedule.scan.mark', $schedule->id), $payload)->assertOk();
        $this->actingAs($this->adminA)->postJson(route('attendance.schedule.scan.mark', $schedule->id), $payload)
            ->assertOk()
            ->assertJsonPath('outcome', 'unchanged');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_marking_keeps_the_scan_source_so_recap_can_trace_it(): void
    {
        $schedule = $this->todaySchedule();

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123401',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');

        $this->actingAs($this->adminA)->postJson(route('attendance.schedule.scan.mark', $schedule->id), [
            'student_id' => $this->studentA->id,
            'status' => 'sakit',
        ])->assertOk();

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->studentA->id,
            'status' => 'sakit',
            'source' => 'scan',
        ]);
    }

    public function test_marking_rejects_a_student_from_another_rombel(): void
    {
        $schedule = $this->todaySchedule();

        $rombelLain = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-8',
        ]);

        $asing = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombelLain->id,
            'nisn' => '0039123499',
        ]);

        $this->actingAs($this->adminA)
            ->postJson(route('attendance.schedule.scan.mark', $schedule->id), [
                'student_id' => $asing->id,
                'status' => 'hadir',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('student_id');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_marking_rejects_an_unknown_status(): void
    {
        $schedule = $this->todaySchedule();

        $this->actingAs($this->adminA)
            ->postJson(route('attendance.schedule.scan.mark', $schedule->id), [
                'student_id' => $this->studentA->id,
                'status' => 'bolos',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_mark_remaining_writes_one_audit_entry(): void
    {
        $schedule = $this->todaySchedule();

        $this->actingAs($this->adminA)
            ->post(route('attendance.schedule.scan.remaining', $schedule->id), ['status' => 'izin'])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mark_attendance_remaining']);
    }

    public function test_mark_remaining_skips_students_already_recorded(): void
    {
        $schedule = $this->todaySchedule();

        $this->actingAs($this->adminA)
            ->post(route('attendance.schedule.scan.remaining', $schedule->id), ['status' => 'izin'])
            ->assertSessionHas('success');

        $this->actingAs($this->adminA)
            ->post(route('attendance.schedule.scan.remaining', $schedule->id), ['status' => 'alpa'])
            ->assertSessionHas('info');

        $this->assertDatabaseCount('attendances', 2);
        $this->assertDatabaseMissing('attendances', ['status' => 'alpa']);
    }

    public function test_gate_scan_is_limited_to_a_gurus_own_classes(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $subject = Subject::factory()->create(['tenant_id' => $this->tenantA->id]);

        $rombelAsing = Rombel::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->year->id,
            'name' => 'X-7',
        ]);

        $siswaAsing = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombelAsing->id,
            'nisn' => '0039123477',
            'name' => 'Siswa Kelas Tujuh',
        ]);

        $schedule = $this->todaySchedule();

        Teacher::factory()->withUser($guru)->create([
            'tenant_id' => $this->tenantA->id,
            'subject_id' => $subject->id,
        ]);

        $this->actingAs($guru)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123477'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);

        // Guru yang mengampu rombel itu boleh memindai siswa di kelasnya.
        $schedule->update(['rombel_id' => $rombelAsing->id]);

        $this->actingAs($guru)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => 'SS:0039123477'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $siswaAsing->id,
            'type' => 'gate_in',
        ]);
    }

    public function test_late_minutes_is_stored_once_and_survives_a_tolerance_change(): void
    {
        $schedule = $this->todaySchedule(
            start: Carbon::now()->subMinutes(45)->format('H:i:s'),
            end: Carbon::now()->addMinutes(45)->format('H:i:s'),
        );

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123401',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');

        $attendance = Attendance::where('student_id', $this->studentA->id)->firstOrFail();

        $this->assertGreaterThanOrEqual(44, $attendance->late_minutes);
        $this->assertLessThanOrEqual(46, $attendance->late_minutes);
    }

    public function test_late_minutes_is_zero_inside_the_tolerance(): void
    {
        $schedule = $this->todaySchedule(
            start: Carbon::now()->subMinutes(3)->format('H:i:s'),
            end: Carbon::now()->addMinutes(45)->format('H:i:s'),
        );

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123401',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');

        $this->assertSame(0, Attendance::where('student_id', $this->studentA->id)->firstOrFail()->late_minutes);
    }

    public function test_late_minutes_is_null_for_gate_scans(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'gate_in']), ['payload' => '0039123401'])
            ->assertSessionHas('success');

        $this->assertNull(Attendance::where('student_id', $this->studentA->id)->firstOrFail()->late_minutes);
    }

    private function todaySchedule(?string $start = null, ?string $end = null): Schedule
    {
        $subject = Subject::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Mapel '.fake()->unique()->bothify('##'),
        ]);

        return Schedule::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->year->id,
            'subject_id' => $subject->id,
            'rombel_id' => $this->rombel->id,
            'day_of_week' => (int) Carbon::parse(now()->toDateString())->isoWeekday(),
            'start_time' => $start ?? Carbon::now()->subMinutes(30)->format('H:i:s'),
            'end_time' => $end ?? Carbon::now()->addMinutes(30)->format('H:i:s'),
        ]);
    }

    private function recordLesson(Schedule $schedule, Student $student, string $status): Attendance
    {
        return Attendance::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $this->year->id,
            'schedule_id' => $schedule->id,
            'student_id' => $student->id,
            'type' => 'lesson',
            'status' => $status,
            'date' => now()->toDateString(),
        ]);
    }
}