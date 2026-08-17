<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed a default platform administrator account.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@playsnooker.bet'],
            [
                'name' => 'Play Snooker Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'currency_preference' => 'USD',
                'is_active' => true,
                'is_admin' => true,
            ],
        );

        Profile::query()->updateOrCreate(
            ['user_id' => $admin->id],
            ['level' => 99, 'xp_points' => 0],
        );

        foreach (['USD', 'GBP', 'EUR', 'NGN', 'BTC', 'USDT'] as $currency) {
            Wallet::query()->firstOrCreate([
                'user_id' => $admin->id,
                'currency' => $currency,
            ], ['balance' => 0, 'ledger' => []]);
        }
    }
}
