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
                'appearance' => ['shaft_color' => '#c1935c', 'wrap_color' => '#4a301d', 'tip_color' => '#2b6cb0', 'butt_color' => '#1f140c'],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Carbon Fiber Pro Cue',
                'type' => 'cue',
                'description' => 'Premium carbon fiber cue favoured by tournament pros. Boosts control significantly.',
                'price' => 19.99,
                'stats_bonus' => ['aim' => 5, 'control' => 6],
                'appearance' => ['shaft_color' => '#1a1a1a', 'wrap_color' => '#0d3924', 'tip_color' => '#e3b02b', 'butt_color' => '#050505'],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Golden Break Cue',
                'type' => 'cue',
                'description' => 'A rare gilded cue for players who like to make a statement.',
                'price' => 49.99,
                'stats_bonus' => ['aim' => 7, 'control' => 7, 'power' => 4],
                'appearance' => ['shaft_color' => '#e3b02b', 'wrap_color' => '#3c2b0c', 'tip_color' => '#c94b3c', 'butt_color' => '#7d5810'],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Crimson Viper Cue',
                'type' => 'cue',
                'description' => 'A striking crimson-and-black cue with a razor-sharp tip for accurate potting.',
                'price' => 12.99,
                'stats_bonus' => ['aim' => 4, 'control' => 3],
                'appearance' => ['shaft_color' => '#7a1f1f', 'wrap_color' => '#151515', 'tip_color' => '#3c6fc9', 'butt_color' => '#0a0a0a'],
                'is_tradeable' => true,
            ],
            [
                'name' => 'Arctic Frost Cue',
                'type' => 'cue',
                'description' => 'A cool-toned pearlescent cue favoured for its steady, controlled strikes.',
                'price' => 15.99,
                'stats_bonus' => ['aim' => 3, 'control' => 5],
                'appearance' => ['shaft_color' => '#dce6ea', 'wrap_color' => '#3c6fc9', 'tip_color' => '#8c3ca0', 'butt_color' => '#1a2a33'],
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
