<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">My Bets</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-4">
        <div class="glass-panel divide-y divide-white/5">
            @forelse ($bets as $bet)
                <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium">
                            Match #{{ $bet->match_id }}
                            @if ($bet->match)
                                &mdash; {{ $bet->match->player1?->name ?? 'TBD' }} vs {{ $bet->match->player2?->name ?? 'TBD' }}
                            @endif
                        </p>
                        <p class="text-sm text-baize-200/60">{{ ucfirst(str_replace('_', ' ', $bet->type)) }} &middot; Stake {{ number_format($bet->amount, 2) }} {{ $bet->currency }} @ odds {{ $bet->odds }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($bet->payout)
                            <span class="text-sm text-gold-300 font-semibold">{{ number_format($bet->payout, 2) }} {{ $bet->currency }}</span>
                        @endif
                        <span class="badge {{ match($bet->status) {
                            'won' => 'bg-baize-400/20 text-baize-200',
                            'lost' => 'bg-red-500/20 text-red-300',
                            'pending' => 'bg-gold-500/20 text-gold-200',
                            default => 'bg-white/10',
                        } }}">{{ $bet->status }}</span>
                    </div>
                </div>
            @empty
                <p class="p-8 text-center text-baize-200/50">You haven't placed any bets yet. Head to the <a href="{{ route('game.lobby') }}" class="text-gold-300 underline">lobby</a> to find a live match.</p>
            @endforelse
        </div>

        {{ $bets->links() }}
    </div>
</x-app-layout>
