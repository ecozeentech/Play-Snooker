<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">{{ $match->player1?->name ?? 'TBD' }} vs {{ $match->player2?->name ?? 'TBD' }}</h2>
            <span class="badge {{ $match->status === 'live' ? 'bg-red-500/20 text-red-300' : 'bg-white/10' }}">{{ ucfirst($match->status) }}</span>
        </div>
    </x-slot>

    <div
        class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid lg:grid-cols-3 gap-6"
        x-data="matchGame({
            matchId: {{ $match->id }},
            player1Id: {{ $match->player1_id ?? 'null' }},
            player2Id: {{ $match->player2_id ?? 'null' }},
            currentUserId: {{ auth()->id() ?? 'null' }},
            initialFrame: {{ $match->current_frame }},
            cues: @js($cues),
        })"
    >
        <div class="lg:col-span-2 space-y-4">
            {{--
                x-show (not x-if/template) is used here deliberately: it keeps
                the canvas permanently in the DOM (just toggling CSS display),
                so `x-ref="canvas"` is always resolvable the instant the Alpine
                component initializes. With x-if, the canvas wouldn't exist in
                the DOM yet at that point (x-init runs before x-if is
                evaluated), so `this.$refs.canvas` would be undefined and the
                table would silently fail to render.
            --}}
            <div class="glass-panel p-3 sm:p-6" x-show="isMyTurn" x-ref="tableWrapper" :class="isFullscreen && 'flex flex-col justify-center bg-baize-950'">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <p class="text-sm text-gold-200" x-text="message"></p>
                    <select x-model.number="selectedCueId" @change="applySelectedCue()" class="form-input-dark min-h-[36px] text-xs w-auto">
                        <template x-for="cue in cues" :key="cue.id">
                            <option :value="cue.id" x-text="cue.name"></option>
                        </template>
                    </select>
                </div>

                <div class="relative">
                    <canvas x-ref="canvas" class="w-full aspect-[16/9] rounded-xl touch-none select-none" style="touch-action: none;"></canvas>

                    <button
                        type="button"
                        @click="toggleTableFullscreen()"
                        title="Toggle fullscreen"
                        class="absolute top-2 right-2 flex items-center justify-center h-10 w-10 rounded-full bg-black/40 text-white/80 hover:text-gold-300 hover:bg-black/60 backdrop-blur transition"
                    >
                        <svg x-show="!isFullscreen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3" /></svg>
                        <svg x-show="isFullscreen" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V5m0 4H5m4 0L3 3m12 6V5m0 4h4m-4 0l6-6M9 15v4m0-4H5m4 0l-6 6m12-6v4m0-4h4m-4 0l6 6" /></svg>
                    </button>
                </div>

                <div class="mt-3 flex justify-center">
                    <button type="button" class="btn-outline text-xs" @click="concedeFrame()">Concede this frame</button>
                </div>
            </div>

            <div class="glass-panel p-10 text-center" x-show="!isMyTurn">
                <p class="text-3xl mb-3">🎱</p>
                <p class="text-baize-100/80" x-text="message"></p>
            </div>

            <!-- Frame score -->
            <div class="glass-card p-5 flex items-center justify-between">
                <span class="font-medium">{{ $match->player1?->name ?? 'TBD' }}</span>
                <span class="font-display text-xl font-bold text-gold-300">{{ $match->frame_scores['player1'] ?? 0 }} &ndash; {{ $match->frame_scores['player2'] ?? 0 }}</span>
                <span class="font-medium">{{ $match->player2?->name ?? 'TBD' }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Live odds -->
            <div class="glass-panel p-5">
                <h3 class="font-display text-base font-semibold text-gold-200 mb-3">Live odds</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span>{{ $match->player1?->name ?? 'Player 1' }}</span>
                        <span class="font-semibold text-gold-300" x-text="(oddsData?.player1 ?? {{ $match->odds_data['player1'] ?? 1.9 }})"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ $match->player2?->name ?? 'Player 2' }}</span>
                        <span class="font-semibold text-gold-300" x-text="(oddsData?.player2 ?? {{ $match->odds_data['player2'] ?? 1.9 }})"></span>
                    </div>
                </div>
            </div>

            <!-- Betting panel -->
            @auth
                @if (in_array($match->status, ['scheduled', 'live']))
                    <div class="glass-panel p-5">
                        <h3 class="font-display text-base font-semibold text-gold-200 mb-3">Place a bet</h3>
                        <form method="POST" action="{{ route('bets.store', $match) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="type" value="winner">
                            <select name="winner_id" class="form-input-dark w-full min-h-[44px]" required>
                                <option value="{{ $match->player1_id }}">{{ $match->player1?->name ?? 'Player 1' }}</option>
                                <option value="{{ $match->player2_id }}">{{ $match->player2?->name ?? 'Player 2' }}</option>
                            </select>
                            <input type="number" step="0.01" min="{{ config('platform.betting.min_stake') }}" max="{{ config('platform.betting.max_stake') }}" name="amount" placeholder="Stake amount" class="form-input-dark w-full min-h-[44px]" required>
                            <button class="btn-gold w-full">Place bet</button>
                        </form>
                    </div>
                @endif
            @endauth
        </div>
    </div>
</x-app-layout>
