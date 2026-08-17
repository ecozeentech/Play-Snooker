<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    public function definition(): array
    {
        return [
            'referrer_id' => User::factory(),
            'referee_id' => User::factory(),
            'reward_amount' => 5,
            'currency' => 'USD',
            'status' => 'pending',
        ];
    }
}
