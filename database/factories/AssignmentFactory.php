<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'teacher_id' => User::factory()->guru(null),
            'rombel_id' => Rombel::factory(),
            'subject_id' => Subject::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'deadline_at' => now()->addDays(7),
            'attachment_path' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'deadline_at' => now()->subDay(),
        ]);
    }
}
