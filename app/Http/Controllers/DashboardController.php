<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\GameMatch;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->loadMissing('profile', 'wallets');

        $stats = [
            'wallet_balance' => $user->wallet_balance,
            'currency' => $user->currency_preference,
            'win_rate' => $user->profile?->winRate() ?? 0,
            'total_wins' => $user->profile?->total_wins ?? 0,
            'total_losses' => $user->profile?->total_losses ?? 0,
            'active_bets' => $user->bets()->where('status', 'pending')->count(),
            'tournaments_played' => $user->tournamentRegistrations()->count(),
        ];

        $recentBets = $user->bets()->with('match')->latest()->limit(5)->get();
        $upcomingTournaments = Tournament::query()
            ->where('status', 'upcoming')
            ->orderBy('registration_closes_at')
            ->limit(5)
            ->get();

        $liveMatches = GameMatch::query()
            ->where('status', 'live')
            ->with('player1', 'player2')
            ->limit(5)
            ->get();

        $sidebarAd = Advertisement::query()->active()->placement('sidebar')->inRandomOrder()->first();

        return view('dashboard', [
            'stats' => $stats,
            'recentBets' => $recentBets,
            'upcomingTournaments' => $upcomingTournaments,
            'liveMatches' => $liveMatches,
            'sidebarAd' => $sidebarAd,
        ]);
    }
}
