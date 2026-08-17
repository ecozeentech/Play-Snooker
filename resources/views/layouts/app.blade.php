<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a1e2b">
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
        <meta name="description" content="Play Snooker &mdash; live betting, digital pool &amp; snooker matches, tournaments and a player-run marketplace.">

        <title>{{ config('app.name', 'Play Snooker') }}</title>

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|cinzel:600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-baize-950 text-baize-50">
        <noscript>
            <div class="bg-red-900/80 text-red-100 text-sm text-center py-2 px-4">
                JavaScript is disabled or failed to load — the digital game engine, live odds and menus that need it won't work. Every other page (dashboard, tournaments, shop, wallet) still works normally.
            </div>
        </noscript>

        <div class="min-h-screen bg-baize-felt bg-fixed">
            <div class="ambient-glow" aria-hidden="true"></div>

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-white/5">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)">
                    <div class="glass-card px-4 py-3 text-sm text-baize-100 border-baize-400/40">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="glass-card px-4 py-3 text-sm text-red-200 border-red-400/40">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main class="pb-24 lg:pb-16">
                {{ $slot }}
            </main>

            <footer class="hidden lg:block border-t border-white/5 py-8 text-center text-xs text-baize-200/50">
                &copy; {{ now()->year }} Play Snooker &middot; playsnooker.bet
            </footer>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/service-worker.js').catch(() => {});
                });
            }
        </script>
    </body>
</html>
