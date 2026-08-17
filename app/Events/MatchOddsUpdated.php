<?php

namespace App\Events;

use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchOddsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameMatch $match) {}

    public function broadcastOn(): array
    {
        return [new Channel("match.{$this->match->id}")];
    }

    public function broadcastAs(): string
    {
        return 'odds.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'odds' => $this->match->odds_data,
        ];
    }
}
