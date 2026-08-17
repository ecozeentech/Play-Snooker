<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Profile;

/**
 * Computes dynamic betting odds from player stats (win rate, recent form)
 * and in-match frame progress. Odds are recalculated whenever a frame
 * completes (see GameMatchObserver) and cached on the match's `odds_data`
 * column so the frontend can render live-updating prices without
 * recomputing them on every request.
 */
class OddsService
{
    private const MARGIN = 0.05; // Bookmaker margin applied across all outcomes.

    private const MIN_ODDS = 1.02;

    /**
     * @return array{player1: float, player2: float, generated_at: string}
     */
    public function calculateMatchWinnerOdds(GameMatch $match): array
    {
        $p1Strength = $this->playerStrength($match->player1_id);
        $p2Strength = $this->playerStrength($match->player2_id);

        [$p1Strength, $p2Strength] = $this->applyFrameMomentum($match, $p1Strength, $p2Strength);

        $totalStrength = $p1Strength + $p2Strength;
        $p1Probability = $totalStrength > 0 ? $p1Strength / $totalStrength : 0.5;
        $p2Probability = 1 - $p1Probability;

        return [
            'player1' => $this->probabilityToOdds($p1Probability),
            'player2' => $this->probabilityToOdds($p2Probability),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function playerStrength(?int $userId): float
    {
        if ($userId === null) {
            return 1.0;
        }

        $profile = Profile::query()->where('user_id', $userId)->first();

        if (! $profile) {
            return 1.0;
        }

        $winRate = $profile->winRate() / 100;
        $experience = min($profile->level / 50, 1.0);

        // Baseline strength of 1.0, boosted by win rate and experience (both weighted).
        return 1.0 + ($winRate * 1.5) + ($experience * 0.5);
    }

    /**
     * Shift strength based on the current frame score so odds react to
     * in-game momentum ("match flow" betting requirement).
     */
    private function applyFrameMomentum(GameMatch $match, float $p1Strength, float $p2Strength): array
    {
        $scores = $match->frame_scores ?? [];
        $p1Frames = (int) ($scores['player1'] ?? 0);
        $p2Frames = (int) ($scores['player2'] ?? 0);

        $frameDiff = $p1Frames - $p2Frames;

        // Each frame of lead multiplies the leading player's strength by 15%.
        $momentum = 1 + (abs($frameDiff) * 0.15);

        if ($frameDiff > 0) {
            $p1Strength *= $momentum;
        } elseif ($frameDiff < 0) {
            $p2Strength *= $momentum;
        }

        return [$p1Strength, $p2Strength];
    }

    private function probabilityToOdds(float $probability): float
    {
        $probability = max(min($probability, 0.98), 0.02);

        // Apply bookmaker margin so payouts stay platform-sustainable.
        $fairOdds = 1 / $probability;
        $marginedOdds = $fairOdds * (1 - self::MARGIN);

        return round(max($marginedOdds, self::MIN_ODDS), 2);
    }
}
