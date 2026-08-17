<?php

namespace Database\Factories;

use App\Models\Gift;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gift>
 */
class GiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'product_id' => Product::factory(),
            'message' => fake()->sentence(),
            'status' => 'sent',
        ];
    }
}
