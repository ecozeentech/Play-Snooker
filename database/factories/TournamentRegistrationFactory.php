<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentRegistration>
 */
class TournamentRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'user_id' => User::factory(),
            'seeding_position' => null,
            'status' => 'registered',
        ];
    }
}
