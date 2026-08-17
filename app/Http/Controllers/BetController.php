<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Repositories\BetRepository;
use App\Services\BettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BetController extends Controller
{
    public function __construct(
        private readonly BettingService $betting,
        private readonly BetRepository $bets,
    ) {}

    public function index(Request $request): View
    {
        return view('bets.index', [
            'bets' => $this->bets->forUser($request->user()),
        ]);
    }

    /**
     * Betting endpoints are rate-limited (see routes/web.php `throttle:betting`)
     * to guard against automated abuse of the live betting platform.
     */
    public function store(Request $request, GameMatch $match): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:'.config('platform.betting.min_stake'), 'max:'.config('platform.betting.max_stake')],
            'type' => ['required', 'in:winner,frame_winner,total_points_over_under'],
            'winner_id' => ['required_if:type,winner', 'nullable', 'integer'],
            'frame' => ['required_if:type,frame_winner', 'nullable', 'integer', 'min:1'],
            'threshold' => ['required_if:type,total_points_over_under', 'nullable', 'integer', 'min:0'],
            'direction' => ['required_if:type,total_points_over_under', 'nullable', 'in:over,under'],
        ]);

        $selection = match ($data['type']) {
            'winner' => ['winner_id' => (int) $data['winner_id']],
            'frame_winner' => ['frame' => (int) $data['frame'], 'winner_id' => (int) ($data['winner_id'] ?? $match->player1_id)],
            'total_points_over_under' => ['threshold' => (int) $data['threshold'], 'direction' => $data['direction']],
        };

        $this->betting->placeBet(
            $request->user(),
            $match,
            (string) $data['amount'],
            $data['type'],
            $selection,
        );

        return back()->with('success', 'Bet placed! Good luck.');
    }
}
