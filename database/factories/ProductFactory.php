<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['cue', 'booster', 'table_skin', 'avatar_frame']);

        return [
            'name' => fake()->words(3, true),
            'type' => $type,
            'description' => fake()->sentence(),
            'image_url' => null,
            'price' => fake()->randomFloat(2, 1, 100),
            'currency' => 'USD',
            'stats_bonus' => match ($type) {
                'cue' => ['aim' => fake()->numberBetween(1, 10), 'control' => fake()->numberBetween(1, 10)],
                'booster' => ['xp_multiplier' => fake()->randomFloat(2, 1.1, 2.0)],
                default => [],
            },
            'duration_minutes' => $type === 'booster' ? fake()->randomElement([30, 60, 120, 1440]) : null,
            'is_giftable' => true,
            'is_tradeable' => fake()->boolean(30),
            'is_active' => true,
        ];
    }
}
