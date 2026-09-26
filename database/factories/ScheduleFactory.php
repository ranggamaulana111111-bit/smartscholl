<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\Teacher;
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
        $nonce = fake()->unique()->bothify('##');
        $tenant = Tenant::factory()->create();
        $user = User::factory()->guru($tenant->id)->create();
        $subject = Subject::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Mapel '.$nonce,
        ]);
        $teacher = Teacher::factory()->withUser($user)->create([
            'tenant_id' => $tenant->id,
            'subject_id' => $subject->id,
        ]);
        $year = AcademicYear::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => '2026-'.$nonce,
        ]);
        $rombel = Rombel::factory()->create([
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'name' => 'X-'.$nonce,
        ]);

        return [
            'tenant_id' => $tenant->id,
            'academic_year_id' => $year->id,
            'teacher_id' => $teacher->id,
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'day_of_week' => 1,
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
        ];
    }
}
