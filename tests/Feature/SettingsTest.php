<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private User $adminA;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'sman1.smartschool.id']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'smpharapan.smartschool.id']);

        $this->adminA = User::factory()->adminSekolah($this->tenantA->id)->create();
        $this->adminB = User::factory()->adminSekolah($this->tenantB->id)->create();
    }

    public function test_guest_cannot_access_settings(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
        $this->post(route('settings.update'))->assertRedirect(route('login'));
    }

    public function test_non_admin_roles_cannot_access_settings(): void
    {
        $guru = User::factory()->guru($this->tenantA->id)->create();
        $siswa = User::factory()->siswa($this->tenantA->id)->create();

        $this->actingAs($guru)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($siswa)->get(route('settings.index'))->assertForbidden();
        $this->actingAs($guru)->post(route('settings.update'), ['group' => 'penilaian'])->assertForbidden();
    }

    public function test_admin_sekolah_sees_tenant_scoped_tabs_only(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Profil Sekolah')
            ->assertSee('Penilaian')
            ->assertSee('Peringatan Dini')
            ->assertSee('Kehadiran')
            ->assertDontSee('Sistem / SaaS');
    }

    public function test_super_admin_sees_and_can_save_sistem_group(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Sistem / SaaS');

        $this->actingAs($super)
            ->post(route('settings.update'), [
                'group' => 'sistem',
                'app_name' => 'Smart School Pro',
                'brand_glyph' => 'S',
            ])
            ->assertSessionHas('success')
            ->assertRedirect();

        $this->assertSame('Smart School Pro', setting('sistem.app_name'));
        $this->assertDatabaseHas('settings', [
            'tenant_id' => null,
            'group' => 'sistem',
            'key' => 'app_name',
            'value' => 'Smart School Pro',
        ]);
    }

    public function test_admin_sekolah_cannot_save_sistem_group(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'sistem',
                'app_name' => 'Ubah Aplikasi',
                'brand_glyph' => 'X',
            ])
            ->assertForbidden();
    }

    public function test_update_penilaian_persists_and_helper_reflects(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'penilaian',
                'kkm' => 80,
                'bobot_tugas' => 10,
                'bobot_formatif' => 40,
                'bobot_uts' => 25,
                'bobot_uas' => 25,
            ])
            ->assertSessionHas('success')
            ->assertRedirect();

        $this->assertSame(80, setting('penilaian.kkm'));
        $this->assertSame(10, setting('penilaian.bobot_tugas'));
        $this->assertSame(40, setting('penilaian.bobot_formatif'));

        $this->assertDatabaseHas('settings', [
            'tenant_id' => $this->tenantA->id,
            'group' => 'penilaian',
            'key' => 'kkm',
            'value' => '80',
        ]);
    }

    public function test_penilaian_bobots_must_sum_to_one_hundred(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'penilaian',
                'kkm' => 75,
                'bobot_tugas' => 10,
                'bobot_formatif' => 40,
                'bobot_uts' => 25,
                'bobot_uas' => 10,
            ])
            ->assertSessionHasErrors('bobot_tugas');

        $this->assertDatabaseMissing('settings', [
            'tenant_id' => $this->tenantA->id,
            'group' => 'penilaian',
            'key' => 'bobot_tugas',
        ]);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'penilaian',
                'kkm' => 'abc',
                'bobot_tugas' => 20,
                'bobot_formatif' => 30,
                'bobot_uts' => 25,
                'bobot_uas' => 25,
            ])
            ->assertSessionHasErrors('kkm');
    }

    public function test_settings_are_isolated_per_tenant(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'penilaian',
                'kkm' => 80,
                'bobot_tugas' => 10,
                'bobot_formatif' => 40,
                'bobot_uts' => 25,
                'bobot_uas' => 25,
            ])
            ->assertSessionHas('success');

        $this->assertSame(80, setting('penilaian.kkm'));

        $this->actingAs($this->adminB);
        $this->assertSame(75, setting('penilaian.kkm'));
    }

    public function test_lesson_scan_tolerance_is_consumed_from_settings(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'kehadiran',
                'lesson_scan_early_minutes' => 60,
                'scan_default_status' => 'hadir',
                'gate_out_without_gate_in_note' => 'Pulang tanpa catatan masuk hari ini',
            ])
            ->assertSessionHas('success');

        $year = AcademicYear::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true]);
        $rombel = Rombel::factory()->create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $year->id]);
        $student = Student::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombel->id,
            'nisn' => '0039123456',
        ]);

        $subject = Subject::factory()->create(['tenant_id' => $this->tenantA->id]);
        $schedule = Schedule::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $year->id,
            'user_id' => $this->adminA->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'day_of_week' => (int) Carbon::now()->isoWeekday(),
            'start_time' => Carbon::now()->addMinutes(30)->format('H:i:s'),
            'end_time' => Carbon::now()->addHours(2)->format('H:i:s'),
        ]);

        $this->actingAs($this->adminA)
            ->post(route('attendance.record', ['mode' => 'lesson']), [
                'payload' => 'SS:0039123456',
                'schedule_id' => $schedule->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'schedule_id' => $schedule->id,
            'type' => 'lesson',
        ]);
    }

    public function test_update_writes_audit_log(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('settings.update'), [
                'group' => 'sekolah',
                'npsn' => '20207389',
                'nss' => '',
                'kepala_sekolah' => 'Drs. H. Bambang, M.Pd.',
                'nip_kepala_sekolah' => '196506041990031007',
                'alamat' => '',
                'kelurahan' => '',
                'kecamatan' => '',
                'kota' => '',
                'provinsi' => '',
                'kode_pos' => '',
                'telepon' => '',
                'email' => '',
                'website' => '',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenantA->id,
            'action' => 'update_settings',
            'entity_type' => 'Setting',
        ]);
    }
}
