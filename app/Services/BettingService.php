<?php

namespace App\Services;

use App\Exceptions\InsufficientFundsException;
use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\User;
use App\Notifications\BetResultNotification;
use App\Repositories\BetRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handles bet placement and automatic settlement for the live betting
 * platform. Manual admin overrides are exposed via forceSettle().
 */
class BettingService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly OddsService $odds,
        private readonly BetRepository $bets,
    ) {}

    /**
     * Place a bet against a match. The stake is escrowed from the user's
     * wallet immediately; winnings are credited on settlement.
     *
     * @throws InvalidArgumentException|InsufficientFundsException
     */
    public function placeBet(
        User $user,
        GameMatch $match,
        string $amount,
        string $type,
        array $selection,
        ?string $currency = null,
    ): Bet {
        if (! in_array($match->status, ['scheduled', 'live'], true)) {
            throw new InvalidArgumentException('Betting is closed for this match.');
        }

        $minStake = (string) config('platform.betting.min_stake');
        $maxStake = (string) config('platform.betting.max_stake');

        if (bccomp($amount, $minStake, 8) < 0 || bccomp($amount, $maxStake, 8) > 0) {
            throw new InvalidArgumentException("Stake must be between {$minStake} and {$maxStake}.");
        }

        $currency = $currency ?? $user->currency_preference ?? config('platform.base_currency');
        $odds = $this->resolveOdds($match, $type, $selection);

        return DB::transaction(function () use ($user, $match, $amount, $currency, $type, $selection, $odds) {
            $this->wallets->debit(
                $user,
                $amount,
                $currency,
                'bet_stake',
                "Bet stake on match #{$match->id}",
            );

            return $this->bets->create([
                'match_id' => $match->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
                'odds' => $odds,
                'type' => $type,
                'selection' => $selection,
                'status' => 'pending',
            ]);
        });
    }

    private function resolveOdds(GameMatch $match, string $type, array $selection): float
    {
        $oddsData = $match->odds_data ?? $this->odds->calculateMatchWinnerOdds($match);

        if ($type === 'winner') {
            $side = $selection['winner_id'] === $match->player1_id ? 'player1' : 'player2';

            return (float) ($oddsData[$side] ?? 1.9);
        }

        // Frame-winner and totals markets default to the base match-winner
        // odds unless a bespoke price was supplied by the caller/admin.
        return (float) ($selection['odds'] ?? $oddsData['player1'] ?? 1.9);
    }

    /**
     * Settle every pending bet on a finished match automatically, based on
     * the match's recorded winner and frame scores.
     */
    public function settleMatchBets(GameMatch $match): void
    {
        if (! $match->isFinished()) {
            throw new RuntimeException('Cannot settle bets for a match that has not finished.');
        }

        foreach ($this->bets->pendingForMatch($match) as $bet) {
            $won = $this->didBetWin($match, $bet);
            $this->settleBet($bet, $won ? 'won' : 'lost');
        }
    }

    private function didBetWin(GameMatch $match, Bet $bet): bool
    {
        return match ($bet->type) {
            'winner' => (int) ($bet->selection['winner_id'] ?? null) === $match->winner_id,
            'frame_winner' => $this->frameWinnerBetWon($match, $bet),
            'total_points_over_under' => $this->totalsBetWon($match, $bet),
            default => false,
        };
    }

    private function frameWinnerBetWon(GameMatch $match, Bet $bet): bool
    {
        $frameNumber = $bet->selection['frame'] ?? null;
        $expectedWinner = $bet->selection['winner_id'] ?? null;
        $frames = $match->frame_scores['frames'] ?? [];

        if ($frameNumber === null || ! isset($frames[$frameNumber - 1])) {
            return false;
        }

        return (int) $frames[$frameNumber - 1]['winner_id'] === (int) $expectedWinner;
    }

    private function totalsBetWon(GameMatch $match, Bet $bet): bool
    {
        $total = (int) ($match->frame_scores['total_points'] ?? 0);
        $threshold = (int) ($bet->selection['threshold'] ?? 0);
        $direction = $bet->selection['direction'] ?? 'over';

        return $direction === 'over' ? $total > $threshold : $total < $threshold;
    }

    /**
     * Settle (or force-settle, via admin override) a single bet.
     */
    public function settleBet(Bet $bet, string $status, ?User $admin = null): Bet
    {
        if (! in_array($status, ['won', 'lost', 'cancelled', 'refunded'], true)) {
            throw new InvalidArgumentException("Invalid settlement status [{$status}].");
        }

        return DB::transaction(function () use ($bet, $status, $admin) {
            $bet->refresh();

            if ($bet->status !== 'pending') {
                return $bet;
            }

            $payout = match ($status) {
                'won' => $bet->potentialPayout(),
                'refunded', 'cancelled' => (string) $bet->amount,
                default => null,
            };

            if ($payout !== null) {
                $this->wallets->credit(
                    $bet->user,
                    $payout,
                    $bet->currency,
                    'bet_payout',
                    "Bet #{$bet->id} settled as {$status}".($admin ? " (admin override by #{$admin->id})" : ''),
                );
            }

            $bet->update([
                'status' => $status,
                'payout' => $payout,
                'settled_at' => now(),
            ]);

            if (in_array($status, ['won', 'lost'], true)) {
                $bet->user->notify(new BetResultNotification($bet));
            }

            return $bet;
        });
    }
}
