<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">My Inventory</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
        @forelse ($items as $type => $group)
            <div>
                <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">{{ ucwords(str_replace('_', ' ', $type)) }}s</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($group as $item)
                        <div class="glass-card p-5 flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold">{{ $item->product->name }}</h4>
                                @if ($item->is_equipped)
                                    <span class="badge bg-baize-400/20 text-baize-200">Equipped</span>
                                @endif
                            </div>
                            <p class="text-sm text-baize-200/60 flex-1">{{ $item->product->description }}</p>
                            @if ($item->expires_at)
                                <p class="text-xs text-baize-200/40">Expires {{ $item->expires_at->diffForHumans() }}</p>
                            @endif
                            @unless ($item->is_equipped)
                                <form method="POST" action="{{ route('shop.equip', $item) }}">
                                    @csrf
                                    <button class="btn-outline text-xs w-full">Equip</button>
                                </form>
                            @endunless
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-center text-baize-200/50 py-12">Your inventory is empty. Visit the <a href="{{ route('shop.index') }}" class="text-gold-300 underline">shop</a> to get started.</p>
        @endforelse
    </div>
</x-app-layout>
