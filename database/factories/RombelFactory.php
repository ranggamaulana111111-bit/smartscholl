<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Teacher;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rombel>
 */
class RombelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'homeroom_teacher_id' => null,
            'name' => 'X-1',
            'grade_level' => 'X',
        ];
    }

    public function homeroomTeacher(Teacher $teacher): static
    {
        return $this->state(fn (): array => ['homeroom_teacher_id' => $teacher->id]);
    }
}
