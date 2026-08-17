<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Notifications\TournamentStartingNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Bulk-notifies every registered player once a tournament's bracket has
 * been shuffled and the tournament is starting. Dispatched to the queue
 * (rather than sent inline) so notifying hundreds of participants never
 * blocks the request that triggered the shuffle.
 */
class SendTournamentStartingNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $tournamentId) {}

    public function handle(): void
    {
        $tournament = Tournament::with('registrations.user')->find($this->tournamentId);

        if (! $tournament) {
            return;
        }

        $users = $tournament->registrations->pluck('user')->filter();

        Notification::send($users, new TournamentStartingNotification($tournament));
    }
}
