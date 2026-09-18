<?php

namespace Database\Factories;

use App\Models\Rombel;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['L', 'P']);

        return [
            'tenant_id' => Tenant::factory(),
            'rombel_id' => Rombel::factory(),
            'user_id' => null,
            'nisn' => $this->faker->unique()->numerify('##########'),
            'qr_token' => Str::random(32),
            'rfid_uid' => null,
            'nis' => (string) $this->faker->unique()->numberBetween(1000, 9999),
            'name' => $this->faker->name($gender === 'L' ? 'male' : 'female'),
            'gender' => $gender,
            'birth_date' => $this->faker->dateTimeBetween('-17 years', '-13 years')->format('Y-m-d'),
            'birth_place' => $this->faker->city,
            'address' => $this->faker->address,
            'phone' => $this->faker->numerify('08##########'),
        ];
    }

    public function withUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }
}
