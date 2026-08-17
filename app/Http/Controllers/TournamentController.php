<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function __construct(private readonly TournamentService $tournaments) {}

    public function index(Request $request): View
    {
        $tournaments = Tournament::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->withCount('registrations')
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('tournaments.index', ['tournaments' => $tournaments]);
    }

    public function create(): View
    {
        return view('tournaments.create', [
            'hostingFee' => config('platform.tournament_hosting_fee'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:physical,digital'],
            'format' => ['required', 'in:single_elimination,double_elimination,round_robin'],
            'max_players' => ['required', 'integer', 'in:4,8,16,32,64,128'],
            'entry_fee' => ['nullable', 'numeric', 'min:0'],
            'registration_closes_at' => ['nullable', 'date', 'after:now'],
        ]);

        $tournament = $this->tournaments->createUserHostedTournament($request->user(), $data);

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', 'Tournament created! Players can now register.');
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load(['registrations.user.profile', 'matches.player1', 'matches.player2', 'creator']);

        return view('tournaments.show', [
            'tournament' => $tournament,
            'isRegistered' => auth()->check() && $tournament->registrations->contains('user_id', auth()->id()),
        ]);
    }

    public function register(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->tournaments->register($tournament, $request->user());

        return back()->with('success', 'You are registered for this tournament!');
    }

    public function checkIn(Request $request, Tournament $tournament): RedirectResponse
    {
        $this->tournaments->checkIn($tournament, $request->user());

        return back()->with('success', 'Checked in! Good luck.');
    }
}
