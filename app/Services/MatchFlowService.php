<?php

namespace App\Services;

use App\Events\MatchFrameUpdated;
use App\Events\MatchOddsUpdated;
use App\Models\GameMatch;
use Illuminate\Support\Facades\DB;

/**
 * Drives a live match forward frame-by-frame: records frame results,
 * recalculates dynamic odds based on the new match flow, broadcasts both
 * over the match's live channel, and finalises the match (triggering bet
 * settlement + tournament bracket progression) once a player has won
 * enough frames.
 */
class MatchFlowService
{
    public function __construct(
        private readonly OddsService $odds,
        private readonly BettingService $betting,
        private readonly TournamentService $tournaments,
    ) {}

    public function startMatch(GameMatch $match): GameMatch
    {
        $match->update([
            'status' => 'live',
            'started_at' => $match->started_at ?? now(),
            'odds_data' => $this->odds->calculateMatchWinnerOdds($match),
        ]);

        MatchOddsUpdated::dispatch($match);

        return $match;
    }

    public function recordFrameResult(GameMatch $match, int $frameWinnerId, int $player1Points = 0, int $player2Points = 0): GameMatch
    {
        return DB::transaction(function () use ($match, $frameWinnerId, $player1Points, $player2Points) {
            $scores = $match->frame_scores ?? ['frames' => [], 'player1' => 0, 'player2' => 0, 'total_points' => 0];

            $scores['frames'][] = [
                'frame' => count($scores['frames']) + 1,
                'winner_id' => $frameWinnerId,
                'player1_points' => $player1Points,
                'player2_points' => $player2Points,
            ];

            $scores['player1'] = ($scores['player1'] ?? 0) + ($frameWinnerId === $match->player1_id ? 1 : 0);
            $scores['player2'] = ($scores['player2'] ?? 0) + ($frameWinnerId === $match->player2_id ? 1 : 0);
            $scores['total_points'] = ($scores['total_points'] ?? 0) + $player1Points + $player2Points;

            $match->update([
                'frame_scores' => $scores,
                'current_frame' => count($scores['frames']) + 1,
            ]);

            MatchFrameUpdated::dispatch($match);

            $frameWinsNeeded = $match->frames_to_win;
            $matchWinner = null;

            if ($scores['player1'] >= $frameWinsNeeded) {
                $matchWinner = $match->player1_id;
            } elseif ($scores['player2'] >= $frameWinsNeeded) {
                $matchWinner = $match->player2_id;
            }

            if ($matchWinner) {
                return $this->finishMatch($match, $matchWinner);
            }

            $match->update(['odds_data' => $this->odds->calculateMatchWinnerOdds($match)]);
            MatchOddsUpdated::dispatch($match);

            return $match;
        });
    }

    public function finishMatch(GameMatch $match, int $winnerId): GameMatch
    {
        $match->update([
            'status' => 'finished',
            'winner_id' => $winnerId,
            'ended_at' => now(),
        ]);

        $this->betting->settleMatchBets($match);

        if ($match->tournament) {
            $this->tournaments->advanceWinner($match->tournament, $match);
        }

        MatchFrameUpdated::dispatch($match);

        return $match;
    }
}
