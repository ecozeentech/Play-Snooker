<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queues the (potentially expensive, for very large brackets) shuffle +
 * bracket-generation step so triggering it never blocks an HTTP request.
 */
class ShuffleTournamentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $tournamentId) {}

    public function handle(TournamentService $tournaments): void
    {
        $tournament = Tournament::find($this->tournamentId);

        if (! $tournament || $tournament->status !== 'upcoming') {
            return;
        }

        $tournaments->shuffleAndSeed($tournament);
    }
}
