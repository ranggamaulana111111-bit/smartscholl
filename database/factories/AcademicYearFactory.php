<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->startOfYear()->addMonths(9)->toDateString(),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    public function genap(): static
    {
        return $this->state(fn (): array => ['semester' => 'genap']);
    }
}
