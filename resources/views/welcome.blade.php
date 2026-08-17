<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#0a1e2b">
        <title>{{ config('app.name', 'Play Snooker') }} &mdash; Live Betting, Digital Pool &amp; Tournaments</title>
        <meta name="description" content="Play Snooker bridges physical and digital snooker &amp; pool tournaments with live betting, social multiplayer and an in-game marketplace.">

        <link rel="manifest" href="/manifest.json">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|cinzel:600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-baize-950 text-baize-50">
        <div class="min-h-screen bg-baize-felt bg-fixed">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-3xl">🎱</span>
                    <span class="font-display text-xl font-bold tracking-wide text-gold-300">Play Snooker</span>
                </div>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-gold">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">Log in</a>
                        <a href="{{ route('register') }}" class="btn-gold">Join now</a>
                    @endauth
                </div>
            </nav>

            <!-- Hero -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 text-center">
                <h1 data-aos="fade-up" class="font-display text-4xl sm:text-6xl font-bold tracking-tight">
                    Where <span class="text-gold-300">Snooker</span> meets the future
                </h1>
                <p data-aos="fade-up" data-aos-delay="100" class="mt-6 max-w-2xl mx-auto text-lg text-baize-100/80">
                    Compete in physical &amp; digital tournaments, bet live on real match flow, challenge players worldwide, and trade rare gear &mdash; all on one platform.
                </p>
                <div data-aos="fade-up" data-aos-delay="200" class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="btn-gold px-8 py-3 text-base">Create free account</a>
                    <a href="{{ route('hall-of-fame') }}" class="btn-outline px-8 py-3 text-base">View Hall of Fame</a>
                </div>
            </section>

            <!-- Feature grid -->
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ([
                    ['icon' => '🎯', 'title' => 'Digital Game Engine', 'body' => 'HD physics-based pool &amp; snooker with realistic spin, cushions and an adjustable AI opponent.'],
                    ['icon' => '🏆', 'title' => 'Fair Tournaments', 'body' => 'Randomized bracket shuffling, single/double elimination &amp; round robin formats for physical and digital events.'],
                    ['icon' => '📈', 'title' => 'Live Betting', 'body' => 'Dynamic odds that react to match flow in real time, powered by WebSockets.'],
                    ['icon' => '🛍️', 'title' => 'In-Game Marketplace', 'body' => 'Buy cues, boosters &amp; skins, gift them to friends, or trade rare items via secure escrow.'],
                    ['icon' => '🌍', 'title' => 'Multi-Currency Wallet', 'body' => 'USD, GBP, EUR, NGN, BTC &amp; USDT support with manual and automated payment gateways.'],
                    ['icon' => '🎖️', 'title' => 'Achievements &amp; Replays', 'body' => 'Earn badges, climb the Hall of Fame, and re-watch your last 5 matches frame-by-frame.'],
                ] as $feature)
                    <div data-aos="fade-up" class="glass-card p-6">
                        <span class="text-3xl">{{ $feature['icon'] }}</span>
                        <h3 class="mt-4 font-display text-lg font-semibold text-gold-200">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm text-baize-100/70">{!! $feature['body'] !!}</p>
                    </div>
                @endforeach
            </section>

            <footer class="border-t border-white/5 py-8 text-center text-xs text-baize-200/50">
                &copy; {{ now()->year }} Play Snooker &middot; playsnooker.bet
            </footer>
        </div>
    </body>
</html>
