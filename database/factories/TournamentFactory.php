<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Open',
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['physical', 'digital']),
            'format' => fake()->randomElement(['single_elimination', 'double_elimination', 'round_robin']),
            'status' => 'upcoming',
            'entry_fee' => fake()->randomElement([0, 5, 10, 25, 50]),
            'prize_pool' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'USD',
            'max_players' => fake()->randomElement([8, 16, 32, 64]),
            'registration_closes_at' => now()->addDays(3),
            'started_at' => null,
            'created_by' => User::factory(),
            'is_user_created' => false,
            'hosting_fee_paid' => 0,
            'check_in_enabled' => false,
            'bracket_data' => null,
        ];
    }
}
