<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-password-input id="password" class="block mt-1 w-full"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-password-input id="password_confirmation" class="block mt-1 w-full"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Referral code -->
        <div class="mt-4">
            <x-input-label for="referral_code" :value="__('Referral code (optional)')" />
            <x-text-input id="referral_code" class="block mt-1 w-full" type="text" name="referral_code" :value="old('referral_code', $referralCode ?? '')" autocomplete="off" placeholder="e.g. ABC12345" />
            <p class="mt-1 text-xs text-baize-200/50">Have a friend's code? You'll both get a welcome reward.</p>
            <x-input-error :messages="$errors->get('referral_code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-baize-200/70 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-400 focus:ring-offset-baize-950" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
