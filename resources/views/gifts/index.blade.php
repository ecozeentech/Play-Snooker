<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-gold-200">Gifts</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
        <div>
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Received</h3>
            <div class="glass-panel divide-y divide-white/5">
                @forelse ($received as $gift)
                    <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $gift->product->name }}</p>
                            <p class="text-sm text-baize-200/60">From {{ $gift->sender->name }}</p>
                            @if ($gift->message)
                                <p class="text-sm text-baize-100/80 italic mt-1">&ldquo;{{ $gift->message }}&rdquo;</p>
                            @endif
                        </div>
                        @if ($gift->status === 'sent')
                            <form method="POST" action="{{ route('gifts.claim', $gift) }}">
                                @csrf
                                <button class="btn-gold text-xs">Claim</button>
                            </form>
                        @else
                            <span class="badge bg-baize-400/20 text-baize-200">Claimed</span>
                        @endif
                    </div>
                @empty
                    <p class="p-8 text-center text-baize-200/50">No gifts received yet.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Sent</h3>
            <div class="glass-panel divide-y divide-white/5">
                @forelse ($sent as $gift)
                    <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $gift->product->name }}</p>
                            <p class="text-sm text-baize-200/60">To {{ $gift->receiver->name }}</p>
                        </div>
                        <span class="badge bg-white/10">{{ $gift->status }}</span>
                    </div>
                @empty
                    <p class="p-8 text-center text-baize-200/50">You haven't sent any gifts yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
