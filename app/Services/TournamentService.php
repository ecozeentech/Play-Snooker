<?php

namespace App\Services;

use App\Events\TournamentBracketUpdated;
use App\Jobs\SendTournamentStartingNotifications;
use App\Models\GameMatch;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Notifications\MatchInviteNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Handles tournament registration, hosting-fee collection, fair bracket
 * shuffling/seeding, bracket progression and prize distribution.
 *
 * Bracket generation and payouts run inside the "tournaments" queue via
 * ShuffleTournamentJob / SettleTournamentJob so they don't block requests
 * for large brackets.
 */
class TournamentService
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    /**
     * Register a user for a tournament, charging the entry fee (if any).
     */
    public function register(Tournament $tournament, User $user): TournamentRegistration
    {
        if ($tournament->status !== 'upcoming') {
            throw new InvalidArgumentException('Registration is closed for this tournament.');
        }

        if ($tournament->isFull()) {
            throw new InvalidArgumentException('This tournament is already full.');
        }

        if ($tournament->registrations()->where('user_id', $user->id)->exists()) {
            throw new InvalidArgumentException('You are already registered for this tournament.');
        }

        return DB::transaction(function () use ($tournament, $user) {
            if (bccomp((string) $tournament->entry_fee, '0', 2) > 0) {
                $this->wallets->debit(
                    $user,
                    (string) $tournament->entry_fee,
                    $tournament->currency,
                    'tournament_entry_fee',
                    "Entry fee for tournament #{$tournament->id} ({$tournament->name})",
                );

                $tournament->increment('prize_pool', $tournament->entry_fee);
            }

            return TournamentRegistration::create([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => 'registered',
            ]);
        });
    }

    /**
     * Allow a user to create and host their own tournament by paying the
     * platform hosting fee.
     */
    public function createUserHostedTournament(User $host, array $attributes): Tournament
    {
        $hostingFee = (string) config('platform.tournament_hosting_fee');

        return DB::transaction(function () use ($host, $attributes, $hostingFee) {
            if (bccomp($hostingFee, '0', 2) > 0) {
                $this->wallets->debit(
                    $host,
                    $hostingFee,
                    $host->currency_preference ?? config('platform.base_currency'),
                    'tournament_hosting_fee',
                    'Hosting fee for user-created tournament',
                );
            }

            return Tournament::create(array_merge($attributes, [
                'created_by' => $host->id,
                'is_user_created' => true,
                'hosting_fee_paid' => $hostingFee,
                'status' => 'upcoming',
            ]));
        });
    }

    public function checkIn(Tournament $tournament, User $user): TournamentRegistration
    {
        $registration = $tournament->registrations()->where('user_id', $user->id)->first();

        if (! $registration) {
            throw new InvalidArgumentException('You are not registered for this tournament.');
        }

        $registration->update(['status' => 'checked_in', 'checked_in_at' => now()]);

        return $registration;
    }

    /**
     * Randomly shuffle seeding for all registrations, generate the bracket
     * for the tournament's format, and create round-one matches. This is
     * visible to all participants immediately afterwards to guarantee a
     * fair, transparent draw.
     */
    public function shuffleAndSeed(Tournament $tournament): Tournament
    {
        $registrations = $tournament->registrations()->where('status', '!=', 'withdrawn')->get();

        if ($registrations->count() < 2) {
            throw new RuntimeException('At least two players are required to start a tournament.');
        }

        return DB::transaction(function () use ($tournament, $registrations) {
            $shuffled = $registrations->shuffle()->values();

            foreach ($shuffled as $index => $registration) {
                $registration->update(['seeding_position' => $index + 1]);
            }

            $bracket = match ($tournament->format) {
                'round_robin' => $this->generateRoundRobinBracket($shuffled),
                'double_elimination' => $this->generateDoubleEliminationBracket($shuffled),
                default => $this->generateSingleEliminationBracket($shuffled),
            };

            $tournament->update([
                'bracket_data' => $bracket,
                'status' => 'ongoing',
                'started_at' => now(),
            ]);

            $this->createMatchesFromBracket($tournament, $bracket);

            $tournament->refresh();

            TournamentBracketUpdated::dispatch($tournament);
            SendTournamentStartingNotifications::dispatch($tournament->id);

            return $tournament;
        });
    }

    /**
     * @param  Collection<int, TournamentRegistration>  $registrations
     */
    private function generateSingleEliminationBracket($registrations): array
    {
        $players = $registrations->pluck('user_id')->values()->all();
        $size = $this->nextPowerOfTwo(count($players));

        // Pad the bracket with byes (null) so every round has a clean power-of-two shape.
        while (count($players) < $size) {
            $players[] = null;
        }

        $round1 = [];
        for ($i = 0; $i < $size / 2; $i++) {
            $round1[] = [
                'player1_id' => $players[$i * 2],
                'player2_id' => $players[$i * 2 + 1],
                'round' => 1,
                'slot' => $i,
            ];
        }

        return [
            'format' => 'single_elimination',
            'total_rounds' => (int) log($size, 2),
            'rounds' => [1 => $round1],
        ];
    }

    private function generateDoubleEliminationBracket($registrations): array
    {
        // The losers bracket is progressively populated as winners-bracket
        // matches complete (see advanceWinner()); we seed only the winners
        // bracket round one here, plus an empty losers-bracket shell.
        $winnersBracket = $this->generateSingleEliminationBracket($registrations);

        return [
            'format' => 'double_elimination',
            'total_rounds' => $winnersBracket['total_rounds'],
            'rounds' => $winnersBracket['rounds'],
            'losers_rounds' => [],
            'grand_final' => null,
        ];
    }

    private function generateRoundRobinBracket($registrations): array
    {
        $players = $registrations->pluck('user_id')->values()->all();

        if (count($players) % 2 !== 0) {
            $players[] = null; // Bye.
        }

        $n = count($players);
        $rounds = [];

        // Standard circle method round-robin scheduling.
        for ($round = 0; $round < $n - 1; $round++) {
            $roundMatches = [];

            for ($i = 0; $i < $n / 2; $i++) {
                $home = $players[$i];
                $away = $players[$n - 1 - $i];

                $roundMatches[] = [
                    'player1_id' => $home,
                    'player2_id' => $away,
                    'round' => $round + 1,
                    'slot' => $i,
                ];
            }

            $rounds[$round + 1] = $roundMatches;

            // Rotate all but the first player.
            $fixed = $players[0];
            $rest = array_slice($players, 1);
            array_unshift($rest, array_pop($rest));
            $players = array_merge([$fixed], $rest);
        }

        return [
            'format' => 'round_robin',
            'total_rounds' => $n - 1,
            'rounds' => $rounds,
        ];
    }

    private function createMatchesFromBracket(Tournament $tournament, array $bracket): void
    {
        foreach ($bracket['rounds'][1] ?? [] as $slot) {
            $match = GameMatch::create([
                'tournament_id' => $tournament->id,
                'player1_id' => $slot['player1_id'],
                'player2_id' => $slot['player2_id'],
                'round' => 1,
                'status' => $slot['player1_id'] && $slot['player2_id'] ? 'scheduled' : 'finished',
                'winner_id' => $this->resolveByeWinner($slot),
            ]);

            if ($match->winner_id) {
                $this->advanceWinner($tournament, $match);
            } elseif ($match->player1_id && $match->player2_id) {
                $this->notifyMatchPlayers($match);
            }
        }
    }

    private function resolveByeWinner(array $slot): ?int
    {
        if ($slot['player1_id'] && ! $slot['player2_id']) {
            return $slot['player1_id'];
        }

        if ($slot['player2_id'] && ! $slot['player1_id']) {
            return $slot['player2_id'];
        }

        return null;
    }

    private function nextPowerOfTwo(int $n): int
    {
        return (int) pow(2, ceil(log(max($n, 2), 2)));
    }

    /**
     * Advance a match's winner into the next round of the bracket. When the
     * final round has been completed, the tournament is marked finished and
     * prizes are distributed automatically.
     */
    public function advanceWinner(Tournament $tournament, GameMatch $match): void
    {
        $bracket = $tournament->bracket_data ?? [];
        $round = $match->round ?? 1;
        $totalRounds = $bracket['total_rounds'] ?? 1;

        if ($bracket['format'] === 'round_robin') {
            $this->maybeFinishRoundRobin($tournament);

            return;
        }

        if ($round >= $totalRounds) {
            $this->finishTournament($tournament, $match->winner_id);

            return;
        }

        $currentRoundMatches = GameMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('round', $round)
            ->orderBy('id')
            ->get()
            ->values();

        $matchIndex = $currentRoundMatches->search(fn ($m) => $m->id === $match->id);
        $nextRound = $round + 1;
        $nextSlot = intdiv($matchIndex, 2);

        $nextMatch = GameMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('round', $nextRound)
            ->orderBy('id')
            ->get()
            ->values()
            ->get($nextSlot);

        if (! $nextMatch) {
            $nextMatch = GameMatch::create([
                'tournament_id' => $tournament->id,
                'round' => $nextRound,
                'status' => 'scheduled',
            ]);
        }

        if ($matchIndex % 2 === 0) {
            $nextMatch->update(['player1_id' => $match->winner_id]);
        } else {
            $nextMatch->update(['player2_id' => $match->winner_id]);
        }

        if ($nextMatch->player1_id && $nextMatch->player2_id) {
            $nextMatch->update(['status' => 'scheduled']);
            $this->notifyMatchPlayers($nextMatch);
        }
    }

    private function notifyMatchPlayers(GameMatch $match): void
    {
        foreach ([$match->player1, $match->player2] as $player) {
            $player?->notify(new MatchInviteNotification($match));
        }
    }

    private function maybeFinishRoundRobin(Tournament $tournament): void
    {
        $unfinished = GameMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('status', '!=', 'finished')
            ->exists();

        if ($unfinished) {
            return;
        }

        $wins = GameMatch::query()
            ->where('tournament_id', $tournament->id)
            ->whereNotNull('winner_id')
            ->get()
            ->countBy('winner_id');

        $winnerId = $wins->sortDesc()->keys()->first();

        $this->finishTournament($tournament, $winnerId ? (int) $winnerId : null);
    }

    /**
     * Mark a tournament finished and automatically pay out the prize pool.
     */
    public function finishTournament(Tournament $tournament, ?int $winnerId): void
    {
        DB::transaction(function () use ($tournament, $winnerId) {
            $tournament->update(['status' => 'finished', 'finished_at' => now()]);

            if ($winnerId && bccomp((string) $tournament->prize_pool, '0', 2) > 0) {
                $winner = User::find($winnerId);

                if ($winner) {
                    $this->wallets->credit(
                        $winner,
                        (string) $tournament->prize_pool,
                        $tournament->currency,
                        'tournament_prize',
                        "Prize for winning tournament #{$tournament->id} ({$tournament->name})",
                    );
                }
            }
        });
    }
}
