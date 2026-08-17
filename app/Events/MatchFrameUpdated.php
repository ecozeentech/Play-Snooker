<?php

namespace App\Events;

use App\Models\GameMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFrameUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GameMatch $match) {}

    public function broadcastOn(): array
    {
        return [new Channel("match.{$this->match->id}")];
    }

    public function broadcastAs(): string
    {
        return 'frame.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'status' => $this->match->status,
            'current_frame' => $this->match->current_frame,
            'frame_scores' => $this->match->frame_scores,
            'winner_id' => $this->match->winner_id,
        ];
    }
}
