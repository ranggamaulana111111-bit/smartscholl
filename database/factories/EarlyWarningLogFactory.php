<?php

namespace Database\Factories;

use App\Models\EarlyWarningLog;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EarlyWarningLog>
 */
class EarlyWarningLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'student_id' => Student::factory(),
            'type' => 'low_score',
            'description' => $this->faker->sentence(),
            'trigger_date' => now()->toDateString(),
            'is_resolved' => false,
            'resolved_by' => null,
            'resolved_at' => null,
            'note' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}
