<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentParent>
 */
class StudentParentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory()->state(['role' => 'orang_tua']),
            'student_id' => Student::factory(),
            'relationship' => 'wali',
            'is_primary' => false,
        ];
    }
}
