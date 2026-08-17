<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queues final prize-pool settlement for a finished tournament. Kept as a
 * dedicated job (rather than an inline service call) so payout processing
 * for large-field tournaments never blocks the request/event that
 * completed the final match.
 */
class SettleTournamentPayoutsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $tournamentId, public ?int $winnerId) {}

    public function handle(TournamentService $tournaments): void
    {
        $tournament = Tournament::find($this->tournamentId);

        if (! $tournament || $tournament->status === 'finished') {
            return;
        }

        $tournaments->finishTournament($tournament, $this->winnerId);
    }
}
