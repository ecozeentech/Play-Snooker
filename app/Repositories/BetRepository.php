<?php

namespace App\Repositories;

use App\Models\Bet;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data-access layer for bets, kept separate from BettingService so the
 * settlement/placement business logic never has to know about query
 * construction details.
 */
class BetRepository
{
    public function create(array $attributes): Bet
    {
        return Bet::create($attributes);
    }

    public function pendingForMatch(GameMatch $match): Collection
    {
        return Bet::query()
            ->where('match_id', $match->id)
            ->where('status', 'pending')
            ->get();
    }

    public function forUser(User $user, int $perPage = 20)
    {
        return Bet::query()
            ->where('user_id', $user->id)
            ->with('match')
            ->latest()
            ->paginate($perPage);
    }

    public function totalStakedOnMatchByUser(GameMatch $match, User $user): string
    {
        return (string) Bet::query()
            ->where('match_id', $match->id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->sum('amount');
    }
}
