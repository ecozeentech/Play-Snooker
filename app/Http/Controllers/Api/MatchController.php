<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use Illuminate\Http\JsonResponse;

/**
 * Lightweight JSON API used as a polling fallback for clients that cannot
 * establish a WebSocket connection to Reverb (e.g. Reverb not configured
 * in this environment). The Blade frontend prefers Laravel Echo when
 * available and falls back to polling this endpoint every few seconds.
 */
class MatchController extends Controller
{
    public function odds(GameMatch $match): JsonResponse
    {
        return response()->json([
            'match_id' => $match->id,
            'status' => $match->status,
            'current_frame' => $match->current_frame,
            'frame_scores' => $match->frame_scores,
            'odds_data' => $match->odds_data,
            'winner_id' => $match->winner_id,
        ]);
    }
}
