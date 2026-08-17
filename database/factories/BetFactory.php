<?php

namespace Database\Factories;

use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bet>
 */
class BetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => GameMatch::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 1, 100),
            'currency' => 'USD',
            'odds' => fake()->randomFloat(2, 1.1, 4.5),
            'type' => 'winner',
            'selection' => ['winner_id' => null],
            'status' => 'pending',
            'payout' => null,
        ];
    }
}
