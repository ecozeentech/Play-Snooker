<?php

namespace Database\Factories;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advertisement>
 */
class AdvertisementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->catchPhrase(),
            'image_url' => 'https://picsum.photos/seed/'.fake()->uuid().'/600/300',
            'redirect_url' => fake()->url(),
            'placement' => fake()->randomElement(['sidebar', 'banner', 'popup']),
            'impressions_budget' => fake()->numberBetween(1000, 100000),
            'impressions_served' => 0,
            'clicks_budget' => fake()->numberBetween(50, 5000),
            'clicks_served' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ];
    }
}
