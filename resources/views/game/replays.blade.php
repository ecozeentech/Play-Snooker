<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-gold-200">Match Replays</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-baize-200/60 mb-6">Your last {{ config('platform.game.max_replays_per_user') }} matches are saved automatically so you can review them frame-by-frame.</p>

        <div class="glass-panel divide-y divide-white/5">
            @forelse ($replays as $replay)
                <a href="{{ route('game.replays.show', $replay) }}" class="p-5 flex items-center justify-between hover:bg-white/5 transition">
                    <div>
                        <p class="font-medium">Match #{{ $replay->match_id }}</p>
                        <p class="text-xs text-baize-200/50">{{ $replay->created_at->diffForHumans() }} &middot; {{ $replay->duration_seconds ? gmdate('i:s', $replay->duration_seconds) : '—' }}</p>
                    </div>
                    <span class="text-gold-300 text-sm">Watch &rarr;</span>
                </a>
            @empty
                <p class="p-8 text-center text-baize-200/50">No replays saved yet. Play a practice or ranked frame to generate one!</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
