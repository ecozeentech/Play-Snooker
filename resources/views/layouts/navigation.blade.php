<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/10 bg-baize-950/90 backdrop-blur-xl supports-[backdrop-filter]:bg-baize-950/70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center min-w-0">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="flex items-center gap-2 shrink-0 group">
                    @if ($branding->logoUrl())
                        <img src="{{ $branding->logoUrl() }}" alt="{{ $branding->name() }}" class="h-8 w-auto transition-transform group-hover:scale-105">
                    @else
                        <span class="text-2xl transition-transform group-hover:rotate-12">🎱</span>
                    @endif
                    <span class="font-display text-lg font-bold tracking-wide bg-gradient-to-r from-gold-200 via-gold-300 to-gold-500 bg-clip-text text-transparent">{{ $branding->name() }}</span>
                </a>

                <div class="hidden lg:flex items-center gap-1 ms-8">
                    @auth
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                        <x-nav-link :href="route('game.lobby')" :active="request()->routeIs('game.lobby') || request()->routeIs('game.show')">Play</x-nav-link>
                        <x-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')">Tournaments</x-nav-link>
                        <x-nav-link :href="route('bets.index')" :active="request()->routeIs('bets.*')">Betting</x-nav-link>
                        <x-nav-link :href="route('shop.index')" :active="request()->routeIs('shop.*')">Shop</x-nav-link>

                        <div class="relative" x-data="{ moreOpen: false }" @click.outside="moreOpen = false">
                            <button @click="moreOpen = ! moreOpen" type="button" class="inline-flex items-center gap-1 px-3 py-2 rounded-full text-sm font-medium text-baize-100/70 hover:text-white hover:bg-white/5 transition">
                                More
                                <svg class="h-3.5 w-3.5 fill-current transition-transform" :class="{ 'rotate-180': moreOpen }" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </button>
                            <div x-show="moreOpen" x-transition x-cloak class="absolute z-50 mt-2 w-48 rounded-xl glass-card py-1 origin-top-left">
                                <x-dropdown-link :href="route('escrow.index')">Marketplace</x-dropdown-link>
                                <x-dropdown-link :href="route('hall-of-fame')">Hall of Fame</x-dropdown-link>
                                <x-dropdown-link :href="route('shop.inventory')">Inventory</x-dropdown-link>
                                <x-dropdown-link :href="route('gifts.index')">Gifts</x-dropdown-link>
                                <x-dropdown-link :href="route('game.replays.index')">Replays</x-dropdown-link>
                            </div>
                        </div>
                    @else
                        <x-nav-link :href="route('hall-of-fame')" :active="request()->routeIs('hall-of-fame')">Hall of Fame</x-nav-link>
                    @endauth
                </div>
            </div>

            <div class="hidden lg:flex items-center gap-2">
                @auth
                    <a href="{{ route('wallet.index') }}" class="glass-card flex items-center gap-2 px-3 py-1.5 text-sm font-semibold text-gold-200 hover:border-gold-400/50 transition">
                        <span>💰</span>
                        <span>{{ number_format(auth()->user()->wallet_balance, 2) }} {{ auth()->user()->currency_preference }}</span>
                    </a>

                    <button type="button" title="Enable notifications" class="flex items-center justify-center h-10 w-10 rounded-full text-baize-100/70 hover:text-gold-300 hover:bg-white/5 transition" onclick="window.enablePushNotifications()">
                        🔔
                    </button>

                    <a href="{{ route('profile.edit') }}" title="{{ auth()->user()->name }}" class="flex items-center justify-center h-10 w-10 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-baize-950 font-bold text-sm hover:shadow-glow transition">
                        {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                    </a>

                    @if (auth()->user()->is_admin)
                        <a href="/admin" class="btn-outline !border-gold-400/50 text-gold-300 text-xs">🛠️ Admin</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Log out" class="flex items-center justify-center h-10 w-10 rounded-full text-baize-100/70 hover:text-red-300 hover:bg-white/5 transition">
                            ⏻
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Log in</a>
                    <a href="{{ route('register') }}" class="btn-gold">Join now</a>
                @endauth
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" type="button" class="inline-flex items-center justify-center h-11 w-11 rounded-md text-baize-100 hover:bg-white/10 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu: always present in the DOM (JS only toggles visibility),
         so links work even if the toggle animation/JS has issues. -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden border-t border-white/10 bg-baize-950/95">
        @auth
            <div class="flex items-center gap-3 px-4 py-4 border-b border-white/10">
                <div class="flex items-center justify-center h-11 w-11 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-baize-950 font-bold shrink-0">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </div>
                <div class="min-w-0">
                    <div class="font-medium text-base text-baize-50 truncate">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-gold-300">{{ number_format(auth()->user()->wallet_balance, 2) }} {{ auth()->user()->currency_preference }}</div>
                </div>
            </div>
        @endauth

        <div class="pt-2 pb-3 space-y-1">
            @auth
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">📊 Dashboard</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('game.lobby')">🎯 Play</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('game.practice')">🕹️ Practice</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.*')">🏆 Tournaments</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bets.index')">🎲 Betting</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('shop.index')">🛍️ Shop</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('escrow.index')">🤝 Marketplace</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('wallet.index')">💳 Wallet</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hall-of-fame')">🎖️ Hall of Fame</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.edit')">👤 Profile</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('shop.inventory')">🎒 Inventory</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('gifts.index')">🎁 Gifts</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('game.replays.index')">🎬 Replays</x-responsive-nav-link>

                @if (auth()->user()->is_admin)
                    <x-responsive-nav-link href="/admin">🛠️ Admin panel</x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full min-h-[44px] ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-red-300 hover:bg-white/5 transition">
                        ⏻ Log Out
                    </button>
                </form>
            @else
                <x-responsive-nav-link :href="route('hall-of-fame')">Hall of Fame</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('login')">Log in</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')">Join now</x-responsive-nav-link>
            @endauth
        </div>
    </div>
</nav>

@auth
    {{-- Modern app-style bottom tab bar for quick thumb-reach navigation on mobile. --}}
    <nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden border-t border-white/10 bg-baize-950/95 backdrop-blur-xl pb-[env(safe-area-inset-bottom)]">
        <div class="grid grid-cols-5">
            @php
                $tabs = [
                    ['route' => 'dashboard', 'icon' => '🏠', 'label' => 'Home', 'active' => request()->routeIs('dashboard')],
                    ['route' => 'game.lobby', 'icon' => '🎯', 'label' => 'Play', 'active' => request()->routeIs('game.*')],
                    ['route' => 'tournaments.index', 'icon' => '🏆', 'label' => 'Cups', 'active' => request()->routeIs('tournaments.*')],
                    ['route' => 'bets.index', 'icon' => '🎲', 'label' => 'Bets', 'active' => request()->routeIs('bets.*')],
                    ['route' => 'wallet.index', 'icon' => '💳', 'label' => 'Wallet', 'active' => request()->routeIs('wallet.*')],
                ];
            @endphp
            @foreach ($tabs as $tab)
                <a href="{{ route($tab['route']) }}" class="flex flex-col items-center justify-center gap-0.5 py-2.5 min-h-[56px] text-xs {{ $tab['active'] ? 'text-gold-300' : 'text-baize-200/60' }}">
                    <span class="text-lg leading-none">{{ $tab['icon'] }}</span>
                    <span class="font-medium">{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endauth
