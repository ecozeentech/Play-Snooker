<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\InventoryItem;
use App\Models\MatchReplay;
use App\Models\Product;
use App\Services\MatchFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function __construct(private readonly MatchFlowService $matchFlow) {}

    public function practice(Request $request): View
    {
        return view('game.practice', [
            'difficulties' => config('platform.game.ai_difficulties'),
            'cues' => $this->availableCues($request),
        ]);
    }

    /**
     * The default "House Cue" (always free/available) plus every cue the
     * player owns in their inventory, each with the appearance data the
     * canvas engine needs to render it distinctly.
     */
    private function availableCues(Request $request): array
    {
        $houseCue = [
            'id' => 0,
            'name' => 'House Cue',
            'appearance' => Product::DEFAULT_CUE_APPEARANCE,
            'equipped' => true,
        ];

        $owned = InventoryItem::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('product', fn ($q) => $q->where('type', 'cue'))
            ->with('product')
            ->get()
            ->map(fn (InventoryItem $item) => [
                'id' => $item->product_id,
                'name' => $item->product->name,
                'appearance' => $item->product->cueAppearance(),
                'equipped' => $item->is_equipped,
            ]);

        if ($owned->contains('equipped', true)) {
            $houseCue['equipped'] = false;
        }

        return collect([$houseCue])->merge($owned)->values()->all();
    }

    public function lobby(): View
    {
        $liveMatches = GameMatch::query()
            ->where('status', 'live')
            ->with('player1.profile', 'player2.profile')
            ->get();

        $scheduledMatches = GameMatch::query()
            ->where('status', 'scheduled')
            ->with('player1.profile', 'player2.profile')
            ->limit(10)
            ->get();

        return view('game.lobby', [
            'liveMatches' => $liveMatches,
            'scheduledMatches' => $scheduledMatches,
        ]);
    }

    public function show(Request $request, GameMatch $match): View
    {
        $match->load('player1.profile', 'player2.profile', 'tournament');

        return view('game.show', [
            'match' => $match,
            'cues' => $this->availableCues($request),
        ]);
    }

    /**
     * Called by the active player's client once a frame's outcome is known.
     * Recalculates live odds and broadcasts the update to spectators/bettors,
     * and settles the match automatically once enough frames are won.
     */
    public function recordFrame(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'winner_id' => ['required', 'integer', 'in:'.$match->player1_id.','.$match->player2_id],
            'player1_points' => ['nullable', 'integer', 'min:0'],
            'player2_points' => ['nullable', 'integer', 'min:0'],
        ]);

        $match = $this->matchFlow->recordFrameResult(
            $match,
            (int) $data['winner_id'],
            (int) ($data['player1_points'] ?? 0),
            (int) ($data['player2_points'] ?? 0),
        );

        return response()->json([
            'status' => $match->status,
            'frame_scores' => $match->frame_scores,
            'odds_data' => $match->odds_data,
        ]);
    }

    public function saveReplay(Request $request, GameMatch $match): JsonResponse
    {
        $data = $request->validate([
            'frames' => ['required', 'array'],
            'duration_seconds' => ['nullable', 'integer'],
        ]);

        $replay = MatchReplay::create([
            'match_id' => $match->id,
            'user_id' => $request->user()->id,
            'frames' => $data['frames'],
            'duration_seconds' => $data['duration_seconds'] ?? null,
        ]);

        // Only the last N replays are kept per user (see platform.game.max_replays_per_user).
        $limit = config('platform.game.max_replays_per_user');
        $ids = $request->user()->replays()->orderByDesc('id')->pluck('id');

        if ($ids->count() > $limit) {
            MatchReplay::query()->whereIn('id', $ids->slice($limit))->delete();
        }

        return response()->json(['id' => $replay->id]);
    }

    public function replays(Request $request): View
    {
        $replays = $request->user()->replays()->with('match')->latest()->get();

        return view('game.replays', ['replays' => $replays]);
    }

    public function showReplay(MatchReplay $replay): View
    {
        abort_unless($replay->user_id === auth()->id(), 403);

        return view('game.replay-viewer', ['replay' => $replay]);
    }
}
