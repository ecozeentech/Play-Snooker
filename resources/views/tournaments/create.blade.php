<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl sm:text-3xl font-bold heading-gradient">Host a Tournament</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="glass-panel p-6 sm:p-8">
            <p class="text-sm text-baize-200/70 mb-6">
                Hosting a tournament costs a one-off fee of
                <span class="text-gold-300 font-semibold">{{ number_format($hostingFee, 2) }} {{ config('platform.base_currency') }}</span>,
                deducted from your wallet. You'll be able to shuffle &amp; seed the bracket once enough players register.
            </p>

            <form method="POST" action="{{ route('tournaments.store') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="name" value="Tournament name" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" required :value="old('name')" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="3" class="form-input-dark mt-1 w-full">{{ old('description') }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                            <option value="digital" @selected(old('type') === 'digital')>Digital</option>
                            <option value="physical" @selected(old('type') === 'physical')>Physical (referee-entered results)</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="format" value="Format" />
                        <select id="format" name="format" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                            <option value="single_elimination" @selected(old('format') === 'single_elimination')>Single elimination</option>
                            <option value="double_elimination" @selected(old('format') === 'double_elimination')>Double elimination</option>
                            <option value="round_robin" @selected(old('format') === 'round_robin')>Round robin</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label for="max_players" value="Max players" />
                        <select id="max_players" name="max_players" class="form-input-dark mt-1 w-full min-h-[44px]" required>
                            @foreach ([4, 8, 16, 32, 64, 128] as $size)
                                <option value="{{ $size }}" @selected((int) old('max_players') === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="entry_fee" value="Entry fee (optional)" />
                        <x-text-input id="entry_fee" name="entry_fee" type="number" step="0.01" min="0" class="block mt-1 w-full" :value="old('entry_fee', 0)" />
                    </div>
                </div>

                <div>
                    <x-input-label for="registration_closes_at" value="Registration closes at (optional)" />
                    <x-text-input id="registration_closes_at" name="registration_closes_at" type="datetime-local" class="block mt-1 w-full" :value="old('registration_closes_at')" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Create &amp; pay hosting fee</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
