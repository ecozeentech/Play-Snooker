<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\MatchReplay;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchReplay>
 */
class MatchReplayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_id' => GameMatch::factory(),
            'user_id' => User::factory(),
            'frames' => [],
            'duration_seconds' => fake()->numberBetween(120, 1800),
        ];
    }
}
