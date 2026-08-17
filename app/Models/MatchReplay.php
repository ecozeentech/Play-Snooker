<?php

namespace App\Models;

use Database\Factories\MatchReplayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReplay extends Model
{
    /** @use HasFactory<MatchReplayFactory> */
    use HasFactory;

    protected $fillable = [
        'match_id',
        'user_id',
        'frames',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'frames' => 'array',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
