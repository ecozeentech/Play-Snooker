<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0a1e2b">

        <title>{{ config('app.name', 'Play Snooker') }}</title>

        <link rel="manifest" href="/manifest.json">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|cinzel:600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-baize-50 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-baize-felt bg-fixed px-4">
            <a href="/" class="flex items-center gap-2">
                <span class="text-4xl">🎱</span>
                <span class="font-display text-2xl font-bold tracking-wide text-gold-300">Play Snooker</span>
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 glass-panel">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
