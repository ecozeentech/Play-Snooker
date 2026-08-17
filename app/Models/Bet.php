<?php

namespace App\Models;

use Database\Factories\BetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    /** @use HasFactory<BetFactory> */
    use HasFactory;

    protected $fillable = [
        'match_id',
        'user_id',
        'amount',
        'currency',
        'odds',
        'type',
        'selection',
        'status',
        'payout',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'odds' => 'decimal:3',
            'payout' => 'decimal:8',
            'selection' => 'array',
            'settled_at' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function potentialPayout(): string
    {
        return bcmul((string) $this->amount, (string) $this->odds, 8);
    }
}
