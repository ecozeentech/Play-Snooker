@props(['disabled' => false])

<div x-data="{ show: false }" class="relative">
    <input
        :type="show ? 'text' : 'password'"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'form-input-dark min-h-[44px] pr-12']) }}
    >
    <button
        type="button"
        @click="show = ! show"
        tabindex="-1"
        :aria-label="show ? 'Hide password' : 'Show password'"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-baize-200/50 hover:text-gold-300 transition"
    >
        <svg x-show="! show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <svg x-show="show" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5a10.45 10.45 0 004.293-.916m3.242-2.53A10.478 10.478 0 0022.066 12c-1.292-4.338-5.31-7.5-10.066-7.5a9.958 9.958 0 00-4.293.916m-1.653 1.653a3 3 0 104.243 4.243M9.88 9.88l4.242 4.242M9.88 9.88L3 3m6.88 6.88L21 21" />
        </svg>
    </button>
</div>
