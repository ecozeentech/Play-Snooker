<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => null,
            'player1_id' => User::factory(),
            'player2_id' => User::factory(),
            'round' => 1,
            'current_frame' => 1,
            'frames_to_win' => 1,
            'status' => 'scheduled',
            'winner_id' => null,
            'frame_scores' => [],
            'odds_data' => [
                'player1' => 1.85,
                'player2' => 1.95,
            ],
            'is_streamed' => false,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'status' => 'live',
            'started_at' => now(),
        ]);
    }
}
