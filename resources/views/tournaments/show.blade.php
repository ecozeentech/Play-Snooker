<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-2xl font-bold text-gold-200">{{ $tournament->name }}</h2>
                <p class="text-sm text-baize-200/60">{{ ucfirst($tournament->type) }} &middot; {{ ucwords(str_replace('_', ' ', $tournament->format)) }} &middot; Hosted by {{ $tournament->creator?->name ?? 'Play Snooker' }}</p>
            </div>
            <span class="badge {{ match($tournament->status) {
                'upcoming' => 'bg-gold-500/20 text-gold-200',
                'ongoing' => 'bg-baize-400/20 text-baize-200',
                'finished' => 'bg-white/10 text-baize-100/70',
                default => 'bg-red-500/20 text-red-300',
            } }}">{{ ucfirst($tournament->status) }}</span>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Prize pool</p>
                <p class="mt-1 text-2xl font-bold text-gold-300">{{ number_format($tournament->prize_pool, 2) }} {{ $tournament->currency }}</p>
            </div>
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Players</p>
                <p class="mt-1 text-2xl font-bold">{{ $tournament->registrations->count() }}/{{ $tournament->max_players }}</p>
            </div>
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Entry fee</p>
                <p class="mt-1 text-2xl font-bold">{{ number_format($tournament->entry_fee, 2) }} {{ $tournament->currency }}</p>
            </div>
        </div>

        @if ($tournament->description)
            <p class="text-baize-100/80">{{ $tournament->description }}</p>
        @endif

        @auth
            <div class="flex flex-wrap gap-3">
                @if ($tournament->status === 'upcoming' && ! $isRegistered)
                    <form method="POST" action="{{ route('tournaments.register', $tournament) }}">
                        @csrf
                        <button class="btn-gold">Register for {{ number_format($tournament->entry_fee, 2) }} {{ $tournament->currency }}</button>
                    </form>
                @elseif ($isRegistered && $tournament->check_in_enabled && $tournament->status === 'upcoming')
                    <form method="POST" action="{{ route('tournaments.check-in', $tournament) }}">
                        @csrf
                        <button class="btn-outline">Check in</button>
                    </form>
                @elseif ($isRegistered)
                    <span class="badge bg-baize-400/20 text-baize-200">You're registered</span>
                @endif
            </div>
        @endauth

        <!-- Bracket -->
        <div class="glass-panel p-6" x-data="{ bracket: @js($tournament->bracket_data) }" x-init="
            window.subscribeToTournament({{ $tournament->id }}, { onBracket: (e) => bracket = e.bracket_data });
        ">
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Bracket</h3>

            <template x-if="!bracket">
                <p class="text-sm text-baize-200/50">The bracket will be shuffled &amp; revealed once registration closes. Seeding is randomized live for full transparency.</p>
            </template>

            <template x-if="bracket">
                <div class="overflow-x-auto">
                    <div class="flex gap-8 min-w-max py-2">
                        <template x-for="(matches, round) in (bracket.rounds || {})" :key="round">
                            <div class="space-y-4">
                                <p class="text-xs uppercase tracking-wide text-baize-200/50">Round <span x-text="round"></span></p>
                                <template x-for="(slot, idx) in matches" :key="idx">
                                    <div class="glass-card px-4 py-3 w-56">
                                        <p class="text-sm" x-text="'Player ' + (slot.player1_id ?? 'TBD')"></p>
                                        <p class="text-xs text-baize-200/40 my-1">vs</p>
                                        <p class="text-sm" x-text="'Player ' + (slot.player2_id ?? 'TBD')"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Registrations -->
        <div class="glass-panel p-6">
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Players</h3>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($tournament->registrations as $registration)
                    <div class="flex items-center justify-between glass-card px-4 py-3">
                        <span class="text-sm">{{ $registration->user->name }}</span>
                        @if ($registration->seeding_position)
                            <span class="badge bg-white/10">Seed #{{ $registration->seeding_position }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Matches -->
        @if ($tournament->matches->isNotEmpty())
            <div class="glass-panel p-6">
                <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Matches</h3>
                <div class="space-y-2">
                    @foreach ($tournament->matches as $match)
                        <a href="{{ route('game.show', $match) }}" class="flex items-center justify-between glass-card px-4 py-3 hover:border-gold-400/40 transition">
                            <span class="text-sm">Round {{ $match->round }}: {{ $match->player1?->name ?? 'TBD' }} vs {{ $match->player2?->name ?? 'TBD' }}</span>
                            <span class="badge bg-white/10">{{ ucfirst($match->status) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
