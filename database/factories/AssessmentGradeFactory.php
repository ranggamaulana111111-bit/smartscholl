<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentGrade;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentGrade>
 */
class AssessmentGradeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'assessment_id' => Assessment::factory(),
            'student_id' => Student::factory(),
            'score' => $this->faker->numberBetween(50, 100),
            'note' => null,
        ];
    }

    public function belowKkm(): static
    {
        return $this->state(fn () => ['score' => $this->faker->numberBetween(40, 74)]);
    }
}
