<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Journal;
use App\Models\Rombel;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'user_id' => User::factory()->guru(null),
            'subject_id' => null,
            'rombel_id' => Rombel::factory(),
            'schedule_id' => null,
            'date' => now()->toDateString(),
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'topic' => $this->faker->sentence(4),
            'notes' => null,
            'attendance_filled' => false,
            'status' => 'draft',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => 'closed']);
    }
}
