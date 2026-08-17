<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Game Lobby</h2>
            <a href="{{ route('game.practice') }}" class="btn-gold">Practice mode</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
        <div>
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">🔴 Live now &mdash; spectate &amp; bet</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($liveMatches as $match)
                    <a href="{{ route('game.show', $match) }}" class="glass-card p-5 flex items-center justify-between hover:border-gold-400/40 transition">
                        <div>
                            <p class="font-medium">{{ $match->player1?->name ?? 'TBD' }} vs {{ $match->player2?->name ?? 'TBD' }}</p>
                            <p class="text-xs text-baize-200/50">Frame {{ $match->current_frame }}</p>
                        </div>
                        <span class="badge bg-red-500/20 text-red-300">Live</span>
                    </a>
                @empty
                    <p class="text-baize-200/50 text-sm py-4">No live matches right now.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Scheduled matches</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($scheduledMatches as $match)
                    <a href="{{ route('game.show', $match) }}" class="glass-card p-5 flex items-center justify-between hover:border-gold-400/40 transition">
                        <div>
                            <p class="font-medium">{{ $match->player1?->name ?? 'TBD' }} vs {{ $match->player2?->name ?? 'TBD' }}</p>
                        </div>
                        <span class="badge bg-white/10">Scheduled</span>
                    </a>
                @empty
                    <p class="text-baize-200/50 text-sm py-4">No scheduled matches.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
