<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Marketplace</h2>
            <div class="flex gap-2">
                <a href="{{ route('escrow.mine') }}" class="btn-outline">My listings</a>
                <a href="{{ route('escrow.create') }}" class="btn-gold">List an item</a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-sm text-baize-200/60 mb-6">
            All trades are protected by escrow: your payment is held securely until the item is delivered,
            with a {{ config('platform.escrow_fee_percent') }}% platform fee taken from the seller's proceeds.
        </p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($listings as $listing)
                <div class="glass-card p-5 flex flex-col gap-3" data-aos="fade-up">
                    <h4 class="font-semibold">{{ $listing->title }}</h4>
                    <p class="text-sm text-baize-200/60 flex-1">{{ $listing->description }}</p>
                    <p class="text-xs text-baize-200/40">Sold by {{ $listing->seller->name }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-lg font-bold text-gold-300">{{ number_format($listing->amount, 2) }} {{ $listing->currency }}</span>
                        @auth
                            @if (auth()->id() !== $listing->seller_id)
                                <form method="POST" action="{{ route('escrow.fund', $listing) }}">
                                    @csrf
                                    <button class="btn-gold text-xs">Buy</button>
                                </form>
                            @endif
                        @endauth
                    </div>
                </div>
            @empty
                <p class="col-span-full text-center text-baize-200/50 py-12">No listings yet. Be the first to list an item!</p>
            @endforelse
        </div>

        {{ $listings->links() }}
    </div>
</x-app-layout>
