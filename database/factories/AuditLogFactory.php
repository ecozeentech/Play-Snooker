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
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['wallet.adjust', 'bet.force_settle', 'tournament.create', 'user.deactivate']),
            'auditable_type' => null,
            'auditable_id' => null,
            'before' => [],
            'after' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
