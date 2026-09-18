<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Subject;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'subject_id' => Subject::factory(),
            'rombel_id' => null,
            'teacher_id' => null,
            'category' => 'tugas',
            'title' => $this->faker->sentence(3),
            'description' => null,
            'date' => now()->toDateString(),
            'max_score' => 100,
            'weight_percentage' => 20,
        ];
    }

    public function formatif(): static
    {
        return $this->state(fn (): array => ['category' => 'formatif', 'weight_percentage' => 30]);
    }

    public function uts(): static
    {
        return $this->state(fn (): array => ['category' => 'uts', 'weight_percentage' => 25]);
    }

    public function uas(): static
    {
        return $this->state(fn (): array => ['category' => 'uas', 'weight_percentage' => 25]);
    }
}
