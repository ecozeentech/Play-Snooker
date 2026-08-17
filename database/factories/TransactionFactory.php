<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['credit', 'debit']),
            'amount' => fake()->randomFloat(2, 5, 500),
            'currency' => 'USD',
            'amount_usd' => fake()->randomFloat(2, 5, 500),
            'gateway' => fake()->randomElement(['stripe', 'coinbase_commerce', 'manual_bank', 'manual_crypto']),
            'status' => 'completed',
            'reference' => (string) Str::uuid(),
            'description' => fake()->sentence(),
        ];
    }
}
