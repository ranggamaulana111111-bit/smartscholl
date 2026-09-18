<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'user_id' => User::factory()->guru(null),
            'subject_id' => Subject::factory(),
            'rombel_id' => Rombel::factory(),
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ];
    }
}
