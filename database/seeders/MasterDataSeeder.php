<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('domain', 'sman1.smartschool.id')->first();

        if (! $tenant) {
            return;
        }

        $year = AcademicYear::create([
            'tenant_id' => $tenant->id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'start_date' => '2026-07-13',
            'end_date' => '2026-12-18',
            'is_active' => true,
        ]);

        $guru = User::where('email', 'guru@sman1.smartschool.id')->first();
        $andi = User::where('email', 'andi@sman1.smartschool.id')->first();

        $rombelX1 = Rombel::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $guru?->id,
            'name' => 'X-1',
            'grade_level' => 'X',
        ]);

        $rombelX2 = Rombel::create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => null,
            'name' => 'X-2',
            'grade_level' => 'X',
        ]);

        $students = [
            ['nisn' => '0039123456', 'nis' => '24001', 'name' => 'Andi Santoso', 'gender' => 'L', 'birth_date' => '2009-05-12', 'birth_place' => 'Jakarta', 'rombel_id' => $rombelX1->id, 'user_id' => $andi?->id],
            ['nisn' => '0039123457', 'nis' => '24002', 'name' => 'Budi Hartono', 'gender' => 'L', 'birth_date' => '2009-08-24', 'birth_place' => 'Depok', 'rombel_id' => $rombelX1->id, 'user_id' => null],
            ['nisn' => '0039123458', 'nis' => '24003', 'name' => 'Citra Lestari', 'gender' => 'P', 'birth_date' => '2009-01-30', 'birth_place' => 'Bekasi', 'rombel_id' => $rombelX1->id, 'user_id' => null],
            ['nisn' => '0039123459', 'nis' => '24004', 'name' => 'Dewi Anggraini', 'gender' => 'P', 'birth_date' => '2009-11-07', 'birth_place' => 'Jakarta', 'rombel_id' => $rombelX2->id, 'user_id' => null],
            ['nisn' => '0039123460', 'nis' => '24005', 'name' => 'Eko Prasetyo', 'gender' => 'L', 'birth_date' => '2009-03-19', 'birth_place' => 'Bandung', 'rombel_id' => $rombelX2->id, 'user_id' => null],
        ];

        foreach ($students as $student) {
            Student::create(array_merge($student, ['tenant_id' => $tenant->id]));
        }

        $teachers = [
            ['nuptk' => '7755766655110001', 'name' => 'Budi Hartawan, S.Pd.', 'subject' => 'Matematika', 'employment_status' => 'asn', 'user_id' => $guru?->id],
            ['nuptk' => '7755766655110002', 'name' => 'Siti Rahayu, S.Pd.', 'subject' => 'Bahasa Indonesia', 'employment_status' => 'gty'],
            ['nuptk' => '7755766655110003', 'name' => 'Agus Wijaya, M.Pd.', 'subject' => 'Fisika', 'employment_status' => 'ptt'],
        ];

        foreach ($teachers as $teacher) {
            Teacher::create(array_merge($teacher, ['tenant_id' => $tenant->id]));
        }
    }
}
