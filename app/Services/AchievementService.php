<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;

/**
 * Awards achievement badges to users. Designed to be called from event
 * listeners (e.g. MatchFinished, BetSettled, TournamentWon) rather than
 * directly from controllers, so badges are granted consistently regardless
 * of which flow triggered the underlying event.
 */
class AchievementService
{
    public function award(User $user, string $achievementKey): ?UserAchievement
    {
        $achievement = Achievement::query()->where('key', $achievementKey)->first();

        if (! $achievement) {
            return null;
        }

        return UserAchievement::query()->firstOrCreate([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ], [
            'earned_at' => now(),
        ]);
    }

    public function hasAchievement(User $user, string $achievementKey): bool
    {
        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->whereHas('achievement', fn ($q) => $q->where('key', $achievementKey))
            ->exists();
    }
}
