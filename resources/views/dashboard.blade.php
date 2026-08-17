<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient leading-tight">
            Welcome back, {{ auth()->user()->name }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <!-- Quick actions -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" data-aos="fade-up">
            <a href="{{ route('game.practice') }}" class="glass-card flex flex-col items-center justify-center gap-2 py-6 hover:border-gold-400/40 transition">
                <span class="text-3xl">🎯</span>
                <span class="text-sm font-semibold">Play Now</span>
            </a>
            <a href="{{ route('tournaments.index') }}" class="glass-card flex flex-col items-center justify-center gap-2 py-6 hover:border-gold-400/40 transition">
                <span class="text-3xl">🏆</span>
                <span class="text-sm font-semibold">Join Tournament</span>
            </a>
            <a href="{{ route('wallet.index') }}" class="glass-card flex flex-col items-center justify-center gap-2 py-6 hover:border-gold-400/40 transition">
                <span class="text-3xl">💳</span>
                <span class="text-sm font-semibold">Deposit</span>
            </a>
            <a href="{{ route('bets.index') }}" class="glass-card flex flex-col items-center justify-center gap-2 py-6 hover:border-gold-400/40 transition">
                <span class="text-3xl">🎲</span>
                <span class="text-sm font-semibold">My Bets</span>
            </a>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4" data-aos="fade-up" data-aos-delay="50">
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Wallet balance</p>
                <p class="mt-1 text-2xl font-bold text-gold-300">{{ number_format($stats['wallet_balance'], 2) }} <span class="text-sm text-baize-200/60">{{ $stats['currency'] }}</span></p>
            </div>
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Win rate</p>
                <p class="mt-1 text-2xl font-bold">{{ $stats['win_rate'] }}%</p>
                <p class="text-xs text-baize-200/50">{{ $stats['total_wins'] }}W / {{ $stats['total_losses'] }}L</p>
            </div>
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Active bets</p>
                <p class="mt-1 text-2xl font-bold">{{ $stats['active_bets'] }}</p>
            </div>
            <div class="glass-card p-5">
                <p class="text-xs uppercase tracking-wide text-baize-200/60">Tournaments played</p>
                <p class="mt-1 text-2xl font-bold">{{ $stats['tournaments_played'] }}</p>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Live matches -->
                <div class="glass-panel p-6" data-aos="fade-up">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg font-semibold text-gold-200">Live right now</h3>
                        <a href="{{ route('game.lobby') }}" class="text-sm text-gold-300 hover:underline">View lobby &rarr;</a>
                    </div>

                    @forelse ($liveMatches as $match)
                        <a href="{{ route('game.show', $match) }}" class="flex items-center justify-between py-3 border-t border-white/5 hover:bg-white/5 rounded-lg px-2 -mx-2 transition">
                            <div class="flex items-center gap-2">
                                <span class="badge bg-red-500/20 text-red-300">Live</span>
                                <span class="text-sm">{{ $match->player1?->name ?? 'TBD' }} vs {{ $match->player2?->name ?? 'TBD' }}</span>
                            </div>
                            <span class="text-xs text-baize-200/50">Frame {{ $match->current_frame }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-baize-200/50 py-4">No live matches right now. Start a practice session or check the lobby!</p>
                    @endforelse
                </div>

                <!-- Recent bets -->
                <div class="glass-panel p-6" data-aos="fade-up">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-display text-lg font-semibold text-gold-200">Recent bets</h3>
                        <a href="{{ route('bets.index') }}" class="text-sm text-gold-300 hover:underline">View all &rarr;</a>
                    </div>

                    @forelse ($recentBets as $bet)
                        <div class="flex items-center justify-between py-3 border-t border-white/5">
                            <div>
                                <p class="text-sm font-medium">Match #{{ $bet->match_id }} &middot; {{ ucfirst(str_replace('_', ' ', $bet->type)) }}</p>
                                <p class="text-xs text-baize-200/50">{{ number_format($bet->amount, 2) }} {{ $bet->currency }} @ {{ $bet->odds }}</p>
                            </div>
                            <span class="badge {{ match($bet->status) {
                                'won' => 'bg-baize-400/20 text-baize-200',
                                'lost' => 'bg-red-500/20 text-red-300',
                                default => 'bg-gold-500/20 text-gold-200',
                            } }}">{{ $bet->status }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-baize-200/50 py-4">No bets placed yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <!-- Upcoming tournaments -->
                <div class="glass-panel p-6" data-aos="fade-up" data-aos-delay="100">
                    <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Upcoming tournaments</h3>
                    @forelse ($upcomingTournaments as $tournament)
                        <a href="{{ route('tournaments.show', $tournament) }}" class="block py-3 border-t border-white/5 hover:bg-white/5 rounded-lg px-2 -mx-2 transition">
                            <p class="text-sm font-medium">{{ $tournament->name }}</p>
                            <p class="text-xs text-baize-200/50">{{ ucfirst($tournament->type) }} &middot; {{ $tournament->registrations_count ?? $tournament->registrations()->count() }}/{{ $tournament->max_players }} players</p>
                        </a>
                    @empty
                        <p class="text-sm text-baize-200/50 py-4">No upcoming tournaments.</p>
                    @endforelse
                </div>

                @if ($sidebarAd)
                    <a href="{{ $sidebarAd->redirect_url }}" target="_blank" class="glass-card block overflow-hidden" data-aos="fade-up" data-aos-delay="150">
                        <img src="{{ $sidebarAd->image_url }}" alt="{{ $sidebarAd->title }}" class="w-full h-32 object-cover">
                        <p class="p-3 text-sm font-medium">{{ $sidebarAd->title }}</p>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
