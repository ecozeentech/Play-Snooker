<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Services\TournamentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TournamentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function fundedUser(string $amount = '100'): User
    {
        $user = User::factory()->create();
        app(WalletService::class)->credit($user, $amount, 'USD', 'test', 'Seed funds');

        return $user->refresh();
    }

    public function test_user_can_register_for_a_free_tournament_via_http(): void
    {
        $user = User::factory()->create();
        $tournament = Tournament::factory()->create(['entry_fee' => 0, 'max_players' => 8, 'status' => 'upcoming']);

        $response = $this->actingAs($user)->post(route('tournaments.register', $tournament));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('tournament_registrations', [
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_registering_for_a_paid_tournament_debits_entry_fee_and_grows_prize_pool(): void
    {
        $user = $this->fundedUser('50');
        $tournament = Tournament::factory()->create(['entry_fee' => 15, 'prize_pool' => 0, 'max_players' => 8]);

        app(TournamentService::class)->register($tournament, $user);

        $user->refresh();
        $tournament->refresh();

        $this->assertEquals(35, (float) $user->wallet_balance);
        $this->assertEquals(15, (float) $tournament->prize_pool);
    }

    public function test_user_cannot_register_twice_for_the_same_tournament(): void
    {
        $user = $this->fundedUser();
        $tournament = Tournament::factory()->create(['entry_fee' => 0, 'max_players' => 8]);

        app(TournamentService::class)->register($tournament, $user);

        $this->expectException(\InvalidArgumentException::class);
        app(TournamentService::class)->register($tournament, $user);
    }

    public function test_registration_fails_once_tournament_is_full(): void
    {
        $tournament = Tournament::factory()->create(['entry_fee' => 0, 'max_players' => 1]);
        $first = $this->fundedUser();
        $second = $this->fundedUser();

        app(TournamentService::class)->register($tournament, $first);

        $this->expectException(\InvalidArgumentException::class);
        app(TournamentService::class)->register($tournament, $second);
    }

    public function test_shuffle_and_seed_generates_a_bracket_and_creates_round_one_matches(): void
    {
        // Bus::fake() prevents the bulk notification job from actually
        // running, without interfering with Eloquent model events (unlike
        // Event::fake(), which would also suppress the User model's
        // referral-code generation hook).
        Bus::fake();

        $tournament = Tournament::factory()->create([
            'entry_fee' => 0,
            'max_players' => 4,
            'format' => 'single_elimination',
        ]);

        $players = User::factory()->count(4)->create();
        foreach ($players as $player) {
            TournamentRegistration::factory()->create([
                'tournament_id' => $tournament->id,
                'user_id' => $player->id,
            ]);
        }

        $tournament = app(TournamentService::class)->shuffleAndSeed($tournament);

        $this->assertSame('ongoing', $tournament->status);
        $this->assertNotNull($tournament->bracket_data);
        $this->assertCount(2, $tournament->bracket_data['rounds'][1]);
        $this->assertEquals(4, $tournament->registrations()->whereNotNull('seeding_position')->count());
        $this->assertDatabaseCount('game_matches', 2);
    }

    public function test_creating_a_user_hosted_tournament_charges_the_hosting_fee(): void
    {
        config(['platform.tournament_hosting_fee' => 10]);
        $host = $this->fundedUser('50');

        $tournament = app(TournamentService::class)->createUserHostedTournament($host, [
            'name' => 'Community Cup',
            'type' => 'digital',
            'format' => 'single_elimination',
            'max_players' => 8,
        ]);

        $host->refresh();

        $this->assertEquals(40, (float) $host->wallet_balance);
        $this->assertTrue($tournament->is_user_created);
        $this->assertEquals($host->id, $tournament->created_by);
    }
}
