<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Contact Us</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid md:grid-cols-2 gap-6">
        <div class="glass-panel p-6 sm:p-8 space-y-4">
            <h3 class="font-display text-lg font-semibold text-gold-200">Get in touch</h3>

            @if ($branding->contactEmail())
                <p class="text-sm text-baize-100/80">
                    <span class="text-baize-200/50">Email:</span>
                    <a href="mailto:{{ $branding->contactEmail() }}" class="text-gold-300 hover:underline">{{ $branding->contactEmail() }}</a>
                </p>
            @endif

            @if ($branding->contactPhone())
                <p class="text-sm text-baize-100/80">
                    <span class="text-baize-200/50">Phone:</span>
                    <a href="tel:{{ $branding->contactPhone() }}" class="text-gold-300 hover:underline">{{ $branding->contactPhone() }}</a>
                </p>
            @endif

            @if ($branding->contactAddress())
                <p class="text-sm text-baize-100/80">
                    <span class="text-baize-200/50">Address:</span><br>
                    {{ $branding->contactAddress() }}
                </p>
            @endif

            <p class="text-sm text-baize-200/50">We typically respond within 1-2 business days.</p>
        </div>

        <div class="glass-panel p-6 sm:p-8">
            <form method="POST" action="{{ route('contact.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="name" value="Your name" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" required :value="old('name')" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" value="Your email" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" required :value="old('email')" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="subject" value="Subject" />
                    <x-text-input id="subject" name="subject" class="block mt-1 w-full" required :value="old('subject')" />
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="message" value="Message" />
                    <textarea id="message" name="message" rows="5" class="form-input-dark mt-1 w-full" required>{{ old('message') }}</textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-2" />
                </div>

                <x-primary-button class="w-full justify-center">Send message</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
