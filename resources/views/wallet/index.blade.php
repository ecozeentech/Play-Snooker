<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Wallet</h2>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <!-- Balances -->
        <div class="grid sm:grid-cols-3 gap-4">
            @foreach ($wallets as $wallet)
                <div class="glass-card p-5">
                    <p class="text-xs uppercase tracking-wide text-baize-200/60">{{ $wallet->currency }}</p>
                    <p class="mt-1 text-2xl font-bold text-gold-300">{{ number_format($wallet->balance, $wallet->currency === 'BTC' ? 6 : 2) }}</p>
                </div>
            @endforeach
        </div>

        <form method="POST" action="{{ route('wallet.currency') }}" class="flex items-center gap-3">
            @csrf
            <label for="display-currency" class="text-sm text-baize-200/70">Display currency</label>
            <select id="display-currency" name="currency" onchange="this.form.submit()" class="form-input-dark min-h-[44px] w-auto">
                @foreach ($supportedCurrencies as $currency)
                    <option value="{{ $currency }}" @selected(auth()->user()->currency_preference === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
        </form>

        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Deposit -->
            <div class="glass-panel p-6">
                <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Deposit funds</h3>

                @if ($gateways['manual_bank'] || $gateways['manual_btc'] || $gateways['manual_usdt'])
                    <div class="text-xs text-baize-200/50 mb-4 space-y-1">
                        @if ($gateways['manual_bank'])
                            <p><span class="text-baize-100/80 font-medium">Bank transfer:</span> {!! nl2br(e($bankDetails)) !!}</p>
                        @endif
                        @if ($gateways['manual_btc'])
                            <p><span class="text-baize-100/80 font-medium">BTC address:</span> {{ $btcAddress }}</p>
                        @endif
                        @if ($gateways['manual_usdt'])
                            <p><span class="text-baize-100/80 font-medium">USDT (TRC20) address:</span> {{ $usdtAddress }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('wallet.deposit.manual') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Amount" />
                                <x-text-input name="amount" type="number" step="0.01" min="1" class="block mt-1 w-full" required />
                            </div>
                            <div>
                                <x-input-label value="Currency" />
                                <select name="currency" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                                    @foreach ($supportedCurrencies as $currency)
                                        <option value="{{ $currency }}">{{ $currency }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <x-input-label value="Payment method" />
                            <select name="gateway" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                                @if ($gateways['manual_bank'])<option value="manual_bank">Bank transfer</option>@endif
                                @if ($gateways['manual_btc'])<option value="manual_btc">Bitcoin</option>@endif
                                @if ($gateways['manual_usdt'])<option value="manual_usdt">USDT</option>@endif
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Payment proof (screenshot/receipt)" />
                            <input type="file" name="proof" required class="form-input-dark mt-1 w-full min-h-[44px]" accept=".jpg,.jpeg,.png,.pdf">
                        </div>
                        <x-primary-button>Submit deposit for verification</x-primary-button>
                    </form>
                @else
                    <p class="text-sm text-baize-200/50">No deposit methods are currently configured. Please contact support.</p>
                @endif
            </div>

            <!-- Withdraw -->
            <div class="glass-panel p-6">
                <h3 class="font-display text-lg font-semibold text-gold-200 mb-4">Withdraw funds</h3>
                <form method="POST" action="{{ route('wallet.withdraw') }}" class="space-y-4">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Amount" />
                            <x-text-input name="amount" type="number" step="0.01" min="1" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label value="Currency" />
                            <select name="currency" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                                @foreach ($supportedCurrencies as $currency)
                                    <option value="{{ $currency }}">{{ $currency }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Destination (bank details / crypto address)" />
                        <x-text-input name="destination" class="block mt-1 w-full" required />
                    </div>
                    <x-primary-button>Request withdrawal</x-primary-button>
                </form>
            </div>
        </div>

        <!-- Transactions -->
        <div class="glass-panel">
            <h3 class="font-display text-lg font-semibold text-gold-200 p-6 pb-0">Transaction history</h3>
            <div class="divide-y divide-white/5">
                @forelse ($transactions as $transaction)
                    <div class="p-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $transaction->description }}</p>
                            <p class="text-xs text-baize-200/50">{{ $transaction->created_at->format('d M Y, H:i') }} &middot; {{ $transaction->gateway }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold {{ $transaction->type === 'credit' ? 'text-baize-300' : 'text-red-300' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                            </span>
                            <span class="badge {{ match($transaction->status) {
                                'completed' => 'bg-baize-400/20 text-baize-200',
                                'pending' => 'bg-gold-500/20 text-gold-200',
                                default => 'bg-red-500/20 text-red-300',
                            } }}">{{ $transaction->status }}</span>
                        </div>
                    </div>
                @empty
                    <p class="p-8 text-center text-baize-200/50">No transactions yet.</p>
                @endforelse
            </div>
        </div>

        {{ $transactions->links() }}
    </div>
</x-app-layout>
