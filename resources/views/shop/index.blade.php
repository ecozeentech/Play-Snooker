<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Shop</h2>
            <a href="{{ route('shop.inventory') }}" class="btn-outline">My inventory</a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
        <x-ad-banner placement="banner" />

        @forelse ($products as $type => $items)
            <div data-aos="fade-up">
                <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">{{ ucwords(str_replace('_', ' ', $type)) }}s</h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($items as $product)
                        <div class="glass-card p-5 flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold">{{ $product->name }}</h4>
                                @if ($product->duration_minutes)
                                    <span class="badge bg-white/10">{{ $product->duration_minutes }}m</span>
                                @endif
                            </div>
                            <p class="text-sm text-baize-200/60 flex-1">{{ $product->description }}</p>
                            @if (!empty($product->stats_bonus))
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($product->stats_bonus as $stat => $value)
                                        <span class="badge bg-baize-400/20 text-baize-200">+{{ $value }} {{ ucfirst($stat) }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-lg font-bold text-gold-300">{{ number_format($product->price, 2) }} {{ $product->currency }}</span>
                                <div class="flex gap-2">
                                    <form method="POST" action="{{ route('shop.purchase', $product) }}">
                                        @csrf
                                        <button class="btn-gold text-xs">Buy</button>
                                    </form>
                                    <button type="button" class="btn-outline text-xs" x-data
                                        @click="$dispatch('open-gift-modal', { productId: {{ $product->id }}, productName: @js($product->name) })">
                                        Gift
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-center text-baize-200/50 py-12">The shop is empty right now.</p>
        @endforelse
    </div>

    <!-- Gift modal -->
    <div
        x-data="{ open: false, productId: null, productName: '' }"
        @open-gift-modal.window="open = true; productId = $event.detail.productId; productName = $event.detail.productName"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
        style="display: none;"
    >
        <div class="glass-panel w-full max-w-md p-6" @click.outside="open = false">
            <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Gift <span x-text="productName"></span></h3>
            <form method="POST" :action="'/shop/' + productId + '/gift'">
                @csrf
                <div class="mb-4">
                    <x-input-label value="Recipient username or email" />
                    <input name="username" required class="form-input-dark mt-1 w-full min-h-[44px]">
                </div>
                <div class="mb-4">
                    <x-input-label value="Personal message (optional)" />
                    <textarea name="message" rows="3" class="form-input-dark mt-1 w-full"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-ghost" @click="open = false">Cancel</button>
                    <button type="submit" class="btn-gold">Send gift</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
