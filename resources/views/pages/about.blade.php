<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">About {{ $branding->name() }}</h2>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="glass-panel p-6 sm:p-8 prose prose-invert prose-headings:text-gold-200 prose-a:text-gold-300 max-w-none">
            {!! $branding->aboutUs() !!}
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('contact') }}" class="btn-gold">Get in touch</a>
        </div>
    </div>
</x-app-layout>
