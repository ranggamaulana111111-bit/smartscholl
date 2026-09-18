<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'academic_year_id' => null,
            'schedule_id' => null,
            'student_id' => Student::factory(),
            'recorded_by' => null,
            'type' => 'gate_in',
            'status' => 'hadir',
            'date' => now()->toDateString(),
            'time' => now()->format('H:i:s'),
            'source' => 'scan',
            'note' => null,
        ];
    }

    public function gateIn(): static
    {
        return $this->state(fn () => ['type' => 'gate_in']);
    }

    public function gateOut(): static
    {
        return $this->state(fn () => ['type' => 'gate_out']);
    }

    public function lesson(): static
    {
        return $this->state(fn () => ['type' => 'lesson']);
    }

    public function sakit(): static
    {
        return $this->state(fn () => ['status' => 'sakit', 'source' => 'manual']);
    }

    public function izin(): static
    {
        return $this->state(fn () => ['status' => 'izin', 'source' => 'manual']);
    }

    public function alpha(): static
    {
        return $this->state(fn () => ['status' => 'alpha', 'source' => 'manual']);
    }

    public function recordedAt(string $date, string $time): static
    {
        return $this->state(fn () => ['date' => $date, 'time' => $time]);
    }
}
