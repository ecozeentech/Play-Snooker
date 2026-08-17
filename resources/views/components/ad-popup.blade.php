@php
    $eligiblePopups = \App\Models\Advertisement::query()
        ->active()
        ->placement('popup')
        ->get()
        ->filter(fn ($candidate) => $candidate->hasBudgetRemaining());

    $ad = $eligiblePopups->isNotEmpty() ? $eligiblePopups->random() : null;
@endphp

@if ($ad)
    @php $ad->increment('impressions_served'); @endphp
    <div
        x-data="{ open: false, dismissKey: 'ad-popup-dismissed-{{ $ad->id }}' }"
        x-init="
            const lastDismissed = localStorage.getItem(dismissKey);
            const dayMs = 1000 * 60 * 60 * 24;
            if (!lastDismissed || (Date.now() - Number(lastDismissed)) > dayMs) {
                setTimeout(() => open = true, 1200);
            }
        "
        x-show="open"
        x-cloak
        x-transition
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4"
        @keydown.escape.window="open = false"
    >
        <div class="glass-panel max-w-sm w-full overflow-hidden" @click.outside="open = false; localStorage.setItem(dismissKey, Date.now())">
            <div class="relative">
                <img src="{{ $ad->displayImageUrl() }}" alt="{{ $ad->title }}" class="w-full h-40 object-cover">
                <span class="absolute top-2 right-2 badge bg-black/50 text-white/70 text-[10px]">Ad</span>
                <button
                    type="button"
                    @click="open = false; localStorage.setItem(dismissKey, Date.now())"
                    class="absolute top-2 left-2 flex items-center justify-center h-8 w-8 rounded-full bg-black/50 text-white hover:bg-black/70"
                    aria-label="Close"
                >&times;</button>
            </div>
            <div class="p-5">
                <p class="font-semibold mb-3">{{ $ad->title }}</p>
                <a
                    href="{{ route('ads.click', $ad) }}"
                    target="_blank"
                    rel="noopener sponsored"
                    class="btn-gold w-full justify-center"
                    @click="localStorage.setItem(dismissKey, Date.now())"
                >Learn more</a>
            </div>
        </div>
    </div>
@endif
