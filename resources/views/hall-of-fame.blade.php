<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Hall of Fame</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex gap-3">
            <a href="{{ route('hall-of-fame', ['sort' => 'wins']) }}" class="{{ $sort === 'wins' ? 'btn-gold' : 'btn-outline' }} text-xs">Top wins</a>
            <a href="{{ route('hall-of-fame', ['sort' => 'wallet']) }}" class="{{ $sort === 'wallet' ? 'btn-gold' : 'btn-outline' }} text-xs">Top wallets</a>
        </div>

        <div class="glass-panel divide-y divide-white/5">
            @foreach ($leaderboard as $index => $player)
                <div class="p-4 flex items-center gap-4">
                    <span class="w-8 text-center font-display font-bold {{ $index < 3 ? 'text-gold-300' : 'text-baize-200/50' }}">#{{ $index + 1 }}</span>
                    <div class="flex-1">
                        <p class="font-medium">{{ $player->name }}</p>
                        <p class="text-xs text-baize-200/50">Level {{ $player->profile?->level ?? 1 }}</p>
                    </div>
                    @if ($sort === 'wallet')
                        <span class="text-gold-300 font-semibold">{{ number_format($player->wallet_balance, 2) }} {{ config('platform.base_currency') }}</span>
                    @else
                        <span class="text-gold-300 font-semibold">{{ $player->profile?->total_wins ?? 0 }} wins</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
