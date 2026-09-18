<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'nuptk' => $this->faker->unique()->numerify('##############'),
            'name' => $this->faker->name,
            'nip' => $this->faker->unique()->numerify('##################'),
            'subject_text' => $this->faker->randomElement(['Matematika', 'Bahasa Indonesia', 'Fisika', 'Kimia', 'Biologi', 'Bahasa Inggris']),
            'employment_status' => $this->faker->randomElement(['gty', 'ptt', 'asn']),
            'address' => $this->faker->address,
            'phone' => $this->faker->numerify('08##########'),
        ];
    }

    public function withUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }
}
