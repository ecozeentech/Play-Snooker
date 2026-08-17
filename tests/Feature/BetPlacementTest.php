<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientFundsException;
use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\User;
use App\Services\BettingService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetPlacementTest extends TestCase
{
    use RefreshDatabase;

    private function fundedUser(string $amount = '100'): User
    {
        $user = User::factory()->create();
        app(WalletService::class)->credit($user, $amount, 'USD', 'test', 'Seed funds');

        return $user->refresh();
    }

    public function test_user_can_place_a_winner_bet_via_the_http_endpoint(): void
    {
        $user = $this->fundedUser();
        $match = GameMatch::factory()->create([
            'status' => 'live',
            'odds_data' => ['player1' => 1.8, 'player2' => 2.1],
        ]);

        $response = $this->actingAs($user)->post(route('bets.store', $match), [
            'amount' => 20,
            'type' => 'winner',
            'winner_id' => $match->player1_id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $bet = Bet::first();
        $this->assertNotNull($bet);
        $this->assertSame($user->id, $bet->user_id);
        $this->assertSame('pending', $bet->status);
        $this->assertEquals(20, (float) $bet->amount);
        $this->assertEquals(1.8, (float) $bet->odds);
    }

    public function test_placing_a_bet_debits_the_users_wallet(): void
    {
        $user = $this->fundedUser('50');
        $match = GameMatch::factory()->create(['status' => 'live']);

        app(BettingService::class)->placeBet($user, $match, '30', 'winner', ['winner_id' => $match->player1_id]);

        $user->refresh();
        $this->assertEquals(20, (float) $user->wallet_balance);
    }

    public function test_bet_placement_fails_with_insufficient_funds(): void
    {
        $user = $this->fundedUser('5');
        $match = GameMatch::factory()->create(['status' => 'live']);

        $this->expectException(InsufficientFundsException::class);

        app(BettingService::class)->placeBet($user, $match, '50', 'winner', ['winner_id' => $match->player1_id]);
    }

    public function test_bet_placement_rejected_for_finished_matches(): void
    {
        $user = $this->fundedUser();
        $match = GameMatch::factory()->create(['status' => 'finished']);

        $this->expectException(\InvalidArgumentException::class);

        app(BettingService::class)->placeBet($user, $match, '10', 'winner', ['winner_id' => $match->player1_id]);
    }

    public function test_winning_bet_is_settled_and_paid_out(): void
    {
        $user = $this->fundedUser('100');
        $match = GameMatch::factory()->create(['status' => 'live']);

        $bet = app(BettingService::class)->placeBet($user, $match, '10', 'winner', ['winner_id' => $match->player1_id]);
        $bet->update(['odds' => 2.0]);

        $match->update(['status' => 'finished', 'winner_id' => $match->player1_id]);
        app(BettingService::class)->settleMatchBets($match);

        $bet->refresh();
        $this->assertSame('won', $bet->status);
        $this->assertEquals(20, (float) $bet->payout);

        $user->refresh();
        // Started with 100, staked 10 (-> 90), won back 20 (-> 110).
        $this->assertEquals(110, (float) $user->wallet_balance);
    }

    public function test_losing_bet_is_settled_with_no_payout(): void
    {
        $user = $this->fundedUser('100');
        $match = GameMatch::factory()->create(['status' => 'live']);

        $bet = app(BettingService::class)->placeBet($user, $match, '10', 'winner', ['winner_id' => $match->player2_id]);

        $match->update(['status' => 'finished', 'winner_id' => $match->player1_id]);
        app(BettingService::class)->settleMatchBets($match);

        $bet->refresh();
        $this->assertSame('lost', $bet->status);
        $this->assertNull($bet->payout);

        $user->refresh();
        $this->assertEquals(90, (float) $user->wallet_balance);
    }
}
