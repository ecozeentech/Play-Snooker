<?php

namespace App\Models;

use Database\Factories\GameMatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a single snooker/pool match (the "Match" name is reserved
 * as a PHP keyword, so this model is named GameMatch and mapped to the
 * `game_matches` table).
 */
class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory;

    protected $table = 'game_matches';

    protected $fillable = [
        'tournament_id',
        'player1_id',
        'player2_id',
        'round',
        'current_frame',
        'frames_to_win',
        'status',
        'winner_id',
        'frame_scores',
        'odds_data',
        'is_streamed',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'frame_scores' => 'array',
            'odds_data' => 'array',
            'is_streamed' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class, 'match_id');
    }

    public function replays(): HasMany
    {
        return $this->hasMany(MatchReplay::class, 'match_id');
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }
}
