<?php

namespace Database\Seeders;

use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SystemSettingSeeder::class,
            ProductSeeder::class,
            AchievementSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->seedDemoData();
        }
    }

    private function seedDemoData(): void
    {
        $players = User::factory()
            ->count(16)
            ->create()
            ->each(function (User $user) {
                Wallet::query()->firstOrCreate(
                    ['user_id' => $user->id, 'currency' => $user->currency_preference],
                    ['balance' => 100, 'ledger' => []],
                );
            });

        $tournament = Tournament::factory()->create([
            'name' => 'Play Snooker Launch Cup',
            'type' => 'digital',
            'format' => 'single_elimination',
            'status' => 'upcoming',
            'max_players' => 16,
            'created_by' => User::where('is_admin', true)->first()?->id,
        ]);

        $players->each(fn (User $player) => TournamentRegistration::factory()->create([
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
        ]));

        GameMatch::factory()->create([
            'tournament_id' => null,
            'player1_id' => $players[0]->id,
            'player2_id' => $players[1]->id,
            'status' => 'live',
            'started_at' => now(),
        ]);
    }
}
