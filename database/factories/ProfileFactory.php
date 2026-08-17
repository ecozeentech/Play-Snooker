<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'avatar' => null,
            'xp_points' => fake()->numberBetween(0, 5000),
            'level' => fake()->numberBetween(1, 50),
            'cue_equipped' => null,
            'total_wins' => fake()->numberBetween(0, 100),
            'total_losses' => fake()->numberBetween(0, 100),
            'bio' => fake()->sentence(),
        ];
    }
}
