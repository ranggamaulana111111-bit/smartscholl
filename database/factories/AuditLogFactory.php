<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'user_id' => User::factory(),
            'action' => 'create',
            'entity_type' => 'Journal',
            'entity_id' => fake()->randomDigitNotNull(),
            'old_values' => null,
            'new_values' => ['topic' => 'Pembelajaran'],
            'ip_address' => '127.0.0.1',
        ];
    }
}
