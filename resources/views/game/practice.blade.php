<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Practice Mode</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="practiceGame(@js($cues))">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm text-baize-200/70">AI difficulty</label>
                <select x-model="difficulty" class="form-input-dark min-h-[44px] w-auto">
                    @foreach ($difficulties as $level)
                        <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                    @endforeach
                </select>

                <label class="text-sm text-baize-200/70">Cue</label>
                <select x-model.number="selectedCueId" @change="applySelectedCue()" class="form-input-dark min-h-[44px] w-auto">
                    <template x-for="cue in cues" :key="cue.id">
                        <option :value="cue.id" x-text="cue.name"></option>
                    </template>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('shop.index', ['type' => 'cue']) }}" class="btn-ghost text-xs">🎯 Get more cues</a>
                <button type="button" class="btn-outline text-xs" @click="newRack()">New rack</button>
            </div>
        </div>

        <div class="glass-panel p-3 sm:p-6" x-ref="tableWrapper" :class="isFullscreen && 'flex flex-col justify-center bg-baize-950'">
            <div class="flex items-center justify-between mb-3 text-sm">
                <span class="font-semibold" :class="turn === 'player' ? 'text-gold-300' : 'text-baize-200/60'">You: <span x-text="playerScore"></span></span>
                <span x-text="message" class="text-baize-100/70 text-center flex-1 mx-4 truncate"></span>
                <span class="font-semibold" :class="turn === 'ai' ? 'text-gold-300' : 'text-baize-200/60'">AI: <span x-text="aiScore"></span></span>
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

            <p class="mt-3 text-xs text-baize-200/40 text-center">
                Drag from the cue ball backward, then release to shoot. Longer pull = more power. Use the small ball widget top-right of the table to add side-spin ("english").
                <span class="hidden max-sm:inline">Rotate your device or tap the fullscreen icon for the best view.</span>
            </p>
        </div>
    </div>
</x-app-layout>
