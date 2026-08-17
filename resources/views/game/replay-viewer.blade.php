<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-gold-200">Replay &mdash; Match #{{ $replay->match_id }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ frames: @js($replay->frames), step: 0 }">
        <div class="glass-panel p-6">
            <p class="text-sm text-baize-200/60 mb-4">
                Step through this match's shot-by-shot event log. (Full ball-position scrubbing is on our roadmap &mdash;
                this view currently replays each recorded shot outcome frame-by-frame.)
            </p>

            <template x-if="frames.length === 0">
                <p class="text-baize-200/50 text-center py-8">No events were recorded for this replay.</p>
            </template>

            <template x-if="frames.length > 0">
                <div>
                    <div class="glass-card p-6 text-center">
                        <p class="text-xs uppercase tracking-wide text-baize-200/50">Shot <span x-text="step + 1"></span> of <span x-text="frames.length"></span></p>
                        <p class="mt-2 text-lg font-semibold" x-text="'Turn: ' + frames[step].turn"></p>
                        <p class="mt-1 text-sm text-baize-200/70" x-text="frames[step].potted.length ? ('Potted balls: ' + frames[step].potted.join(', ')) : 'No balls potted'"></p>
                    </div>

                    <div class="flex items-center justify-between mt-4">
                        <button type="button" class="btn-outline text-xs" :disabled="step === 0" @click="step = Math.max(0, step - 1)">&larr; Previous</button>
                        <input type="range" min="0" :max="frames.length - 1" x-model.number="step" class="flex-1 mx-4">
                        <button type="button" class="btn-outline text-xs" :disabled="step === frames.length - 1" @click="step = Math.min(frames.length - 1, step + 1)">Next &rarr;</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>
