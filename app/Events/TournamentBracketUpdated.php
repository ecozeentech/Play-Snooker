<?php

namespace App\Events;

use App\Models\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentBracketUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Tournament $tournament) {}

    public function broadcastOn(): array
    {
        return [new Channel("tournament.{$this->tournament->id}")];
    }

    public function broadcastAs(): string
    {
        return 'bracket.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tournament_id' => $this->tournament->id,
            'status' => $this->tournament->status,
            'bracket_data' => $this->tournament->bracket_data,
        ];
    }
}
