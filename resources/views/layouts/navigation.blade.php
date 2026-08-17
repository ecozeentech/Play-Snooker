<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/5 bg-baize-950/80 backdrop-blur-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="flex items-center gap-2 shrink-0">
                    <span class="text-2xl">🎱</span>
                    <span class="font-display text-lg font-bold tracking-wide text-gold-300">Play Snooker</span>
                </a>

                <div class="hidden lg:flex items-center gap-1 ms-8">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                        <x-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')">Tournaments</x-nav-link>
                        <x-nav-link :href="route('game.lobby')" :active="request()->routeIs('game.lobby') || request()->routeIs('game.show')">Play</x-nav-link>
                        <x-nav-link :href="route('game.practice')" :active="request()->routeIs('game.practice')">Practice</x-nav-link>
                        <x-nav-link :href="route('bets.index')" :active="request()->routeIs('bets.*')">Betting</x-nav-link>
                        <x-nav-link :href="route('shop.index')" :active="request()->routeIs('shop.*')">Shop</x-nav-link>
                        <x-nav-link :href="route('escrow.index')" :active="request()->routeIs('escrow.*')">Marketplace</x-nav-link>
                        <x-nav-link :href="route('hall-of-fame')" :active="request()->routeIs('hall-of-fame')">Hall of Fame</x-nav-link>
                    @else
                        <x-nav-link :href="route('hall-of-fame')" :active="request()->routeIs('hall-of-fame')">Hall of Fame</x-nav-link>
                    @endauth
                </div>
            </div>

            <div class="hidden lg:flex items-center gap-3">
                @auth
                    <a href="{{ route('wallet.index') }}" class="glass-card flex items-center gap-2 px-3 py-1.5 text-sm font-semibold text-gold-200 hover:border-gold-400/50">
                        <span>💰</span>
                        <span>{{ number_format(auth()->user()->wallet_balance, 2) }} {{ auth()->user()->currency_preference }}</span>
                    </a>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="btn-ghost">
                                {{ auth()->user()->name }}
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                            <x-dropdown-link :href="route('shop.inventory')">Inventory</x-dropdown-link>
                            <x-dropdown-link :href="route('gifts.index')">Gifts</x-dropdown-link>
                            <x-dropdown-link :href="route('game.replays.index')">Replays</x-dropdown-link>
                            <button type="button" class="block w-full min-h-[44px] px-4 py-2 text-start text-sm leading-5 text-baize-100 hover:bg-white/10" onclick="window.enablePushNotifications()">
                                🔔 Enable notifications
                            </button>
                            @if (auth()->user()->is_admin)
                                <x-dropdown-link href="/admin">Admin panel</x-dropdown-link>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Log in</a>
                    <a href="{{ route('register') }}" class="btn-gold">Join now</a>
                @endauth
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center h-11 w-11 rounded-md text-baize-100 hover:bg-white/10 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-white/5">
        <div class="pt-2 pb-3 space-y-1">
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')">Tournaments</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('game.lobby')">Play</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('game.practice')">Practice</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bets.index')">Betting</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('shop.index')">Shop</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('escrow.index')">Marketplace</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('wallet.index')">Wallet ({{ number_format(auth()->user()->wallet_balance, 2) }} {{ auth()->user()->currency_preference }})</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hall-of-fame')">Hall of Fame</x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('hall-of-fame')">Hall of Fame</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('login')">Log in</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')">Join now</x-responsive-nav-link>
            @endauth
        </div>

        @auth
            <div class="pt-4 pb-1 border-t border-white/10">
                <div class="px-4">
                    <div class="font-medium text-base text-baize-50">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-baize-200/60">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('shop.inventory')">Inventory</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('gifts.index')">Gifts</x-responsive-nav-link>
                    @if (auth()->user()->is_admin)
                        <x-responsive-nav-link href="/admin">Admin panel</x-responsive-nav-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
