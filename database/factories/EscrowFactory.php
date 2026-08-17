<?php

namespace Database\Factories;

use App\Models\Escrow;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Escrow>
 */
class EscrowFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 500);

        return [
            'seller_id' => User::factory(),
            'buyer_id' => null,
            'product_id' => Product::factory(),
            'title' => fake()->words(4, true),
            'description' => fake()->sentence(),
            'amount' => $amount,
            'currency' => 'USD',
            'fee_amount' => round($amount * 0.05, 2),
            'status' => 'pending',
        ];
    }
}
