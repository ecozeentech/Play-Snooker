<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the default shop catalog: cues, boosters, table skins and avatar frames.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Ash Precision Cue',
                'type' => 'cue',
                'description' => 'A balanced ash cue that improves aim consistency.',
                'price' => 4.99,
                'stats_bonus' => ['aim' => 3, 'control' => 1],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Carbon Fiber Pro Cue',
                'type' => 'cue',
                'description' => 'Premium carbon fiber cue favoured by tournament pros. Boosts control significantly.',
                'price' => 19.99,
                'stats_bonus' => ['aim' => 5, 'control' => 6],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Golden Break Cue',
                'type' => 'cue',
                'description' => 'A rare gilded cue for players who like to make a statement.',
                'price' => 49.99,
                'stats_bonus' => ['aim' => 7, 'control' => 7, 'power' => 4],
                'is_tradeable' => true,
            ],
            [
                'name' => 'XP Booster (30 min)',
                'type' => 'booster',
                'description' => 'Doubles XP earned from matches for 30 minutes.',
                'price' => 1.99,
                'stats_bonus' => ['xp_multiplier' => 2.0],
                'duration_minutes' => 30,
                'is_tradeable' => false,
            ],
            [
                'name' => 'XP Booster (24 hours)',
                'type' => 'booster',
                'description' => 'Doubles XP earned from matches for a full day.',
                'price' => 6.99,
                'stats_bonus' => ['xp_multiplier' => 2.0],
                'duration_minutes' => 1440,
                'is_tradeable' => false,
            ],
            [
                'name' => 'Steady Hand Booster',
                'type' => 'booster',
                'description' => 'Temporarily reduces aim sway for 1 hour of practice or ranked play.',
                'price' => 3.49,
                'stats_bonus' => ['aim' => 2],
                'duration_minutes' => 60,
                'is_tradeable' => false,
            ],
            [
                'name' => 'Emerald Baize Table Skin',
                'type' => 'table_skin',
                'description' => 'Classic emerald green baize with a subtle sheen.',
                'price' => 2.99,
                'stats_bonus' => [],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Midnight Gold Table Skin',
                'type' => 'table_skin',
                'description' => 'Luxury dark table skin with gold cushion trim.',
                'price' => 9.99,
                'stats_bonus' => [],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Royal Crimson Table Skin',
                'type' => 'table_skin',
                'description' => 'Bold crimson felt for high-stakes matches.',
                'price' => 9.99,
                'stats_bonus' => [],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Bronze Rookie Frame',
                'type' => 'avatar_frame',
                'description' => 'A simple bronze frame for new players.',
                'price' => 0.99,
                'stats_bonus' => [],
                'is_tradeable' => false,
            ],
            [
                'name' => 'Gold Champion Frame',
                'type' => 'avatar_frame',
                'description' => 'Show off your tournament wins with this gold frame.',
                'price' => 14.99,
                'stats_bonus' => [],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Diamond Legend Frame',
                'type' => 'avatar_frame',
                'description' => 'The rarest frame, reserved for Hall of Fame legends.',
                'price' => 99.99,
                'stats_bonus' => [],
                'is_tradeable' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['name' => $product['name']],
                array_merge([
                    'currency' => 'USD',
                    'is_giftable' => true,
                    'is_active' => true,
                ], $product),
            );
        }
    }
}
