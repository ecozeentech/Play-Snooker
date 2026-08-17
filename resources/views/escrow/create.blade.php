<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-bold text-gold-200">List an Item for Sale</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="glass-panel p-6 sm:p-8">
            @if ($tradeableItems->isEmpty())
                <p class="text-baize-200/60">You don't have any tradeable items in your inventory yet. Purchase tradeable cues, table skins or avatar frames from the <a href="{{ route('shop.index') }}" class="text-gold-300 underline">shop</a> first.</p>
            @else
                <form method="POST" action="{{ route('escrow.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="inventory_item_id" value="Item to sell" />
                        <select id="inventory_item_id" name="inventory_item_id" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                            @foreach ($tradeableItems as $item)
                                <option value="{{ $item->id }}">{{ $item->product->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="title" value="Listing title" />
                        <x-text-input id="title" name="title" class="block mt-1 w-full" required :value="old('title')" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Description (optional)" />
                        <textarea id="description" name="description" rows="3" class="form-input-dark mt-1 w-full">{{ old('description') }}</textarea>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="amount" value="Price" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label for="currency" value="Currency" />
                            <select id="currency" name="currency" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                                @foreach (['USD', 'GBP', 'EUR', 'NGN', 'BTC', 'USDT'] as $currency)
                                    <option value="{{ $currency }}">{{ $currency }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>List item</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
