<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-gold-200">My Marketplace Activity</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
        <div>
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Selling</h3>
            <div class="glass-panel divide-y divide-white/5">
                @forelse ($selling as $escrow)
                    <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $escrow->title }}</p>
                            <p class="text-sm text-baize-200/60">{{ number_format($escrow->amount, 2) }} {{ $escrow->currency }} &middot; Buyer: {{ $escrow->buyer?->name ?? 'Awaiting buyer' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge {{ match($escrow->status) {
                                'released' => 'bg-baize-400/20 text-baize-200',
                                'disputed' => 'bg-red-500/20 text-red-300',
                                default => 'bg-gold-500/20 text-gold-200',
                            } }}">{{ $escrow->status }}</span>
                            @if ($escrow->buyer_id && $escrow->status === 'pending')
                                <form method="POST" action="{{ route('escrow.dispute', $escrow) }}" onsubmit="return prompt('Reason for dispute:') !== null">
                                    @csrf
                                    <input type="hidden" name="reason" value="Seller reported an issue">
                                    <button class="btn-outline text-xs">Raise dispute</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="p-8 text-center text-baize-200/50">You have no active listings.</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Buying</h3>
            <div class="glass-panel divide-y divide-white/5">
                @forelse ($buying as $escrow)
                    <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $escrow->title }}</p>
                            <p class="text-sm text-baize-200/60">{{ number_format($escrow->amount, 2) }} {{ $escrow->currency }} &middot; Seller: {{ $escrow->seller->name }}</p>
                        </div>
                        <span class="badge {{ match($escrow->status) {
                            'released' => 'bg-baize-400/20 text-baize-200',
                            'disputed' => 'bg-red-500/20 text-red-300',
                            default => 'bg-gold-500/20 text-gold-200',
                        } }}">{{ $escrow->status }}</span>
                    </div>
                @empty
                    <p class="p-8 text-center text-baize-200/50">You haven't purchased anything yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
