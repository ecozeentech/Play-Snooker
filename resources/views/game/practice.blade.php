<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-gold-200">Practice Mode</h2>
    </x-slot>

    @vite('resources/js/game/practice.js')

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="practiceGame()">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <label class="text-sm text-baize-200/70">AI difficulty</label>
                <select x-model="difficulty" class="form-input-dark min-h-[44px] w-auto">
                    @foreach ($difficulties as $level)
                        <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn-outline text-xs" @click="newRack()">New rack</button>
        </div>

        <div class="glass-panel p-3 sm:p-6">
            <div class="flex items-center justify-between mb-3 text-sm">
                <span class="font-semibold" :class="turn === 'player' ? 'text-gold-300' : 'text-baize-200/60'">You: <span x-text="playerScore"></span></span>
                <span x-text="message" class="text-baize-100/70 text-center flex-1 mx-4 truncate"></span>
                <span class="font-semibold" :class="turn === 'ai' ? 'text-gold-300' : 'text-baize-200/60'">AI: <span x-text="aiScore"></span></span>
            </div>

            <canvas x-ref="canvas" class="w-full aspect-[16/9] rounded-xl touch-none select-none" style="touch-action: none;"></canvas>

            <p class="mt-3 text-xs text-baize-200/40 text-center">Drag from the cue ball backward, then release to shoot. Longer pull = more power.</p>
        </div>
    </div>
</x-app-layout>
