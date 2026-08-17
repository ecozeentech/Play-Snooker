<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Seed the default catalog of achievement badges.
     */
    public function run(): void
    {
        $achievements = [
            ['key' => 'first-win', 'name' => 'First Win', 'description' => 'Won your very first match.'],
            ['key' => 'century-break', 'name' => 'Century Break', 'description' => 'Scored 100+ points in a single frame.'],
            ['key' => 'betting-veteran', 'name' => 'Betting Veteran', 'description' => 'Placed 50 bets on the platform.'],
            ['key' => 'high-roller', 'name' => 'High Roller', 'description' => 'Placed a single bet of $100 or more.'],
            ['key' => 'tournament-champion', 'name' => 'Tournament Champion', 'description' => 'Won a tournament.'],
            ['key' => 'hosting-hero', 'name' => 'Hosting Hero', 'description' => 'Hosted your first user-created tournament.'],
            ['key' => 'referral-rockstar', 'name' => 'Referral Rockstar', 'description' => 'Referred 10 friends to Play Snooker.'],
            ['key' => 'trade-master', 'name' => 'Trade Master', 'description' => 'Completed 10 escrow trades.'],
            ['key' => 'unbeaten-streak', 'name' => 'Unbeaten Streak', 'description' => 'Won 10 matches in a row.'],
            ['key' => 'shop-collector', 'name' => 'Shop Collector', 'description' => 'Owned 20 items in your inventory.'],
        ];

        foreach ($achievements as $achievement) {
            Achievement::query()->updateOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
