<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'is_equipped' => false,
            'expires_at' => null,
            'acquired_at' => now(),
        ];
    }
}
