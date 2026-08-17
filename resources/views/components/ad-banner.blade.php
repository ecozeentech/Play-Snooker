@props(['placement'])

@php
    $eligibleAds = \App\Models\Advertisement::query()
        ->active()
        ->placement($placement)
        ->get()
        ->filter(fn ($candidate) => $candidate->hasBudgetRemaining());

    $ad = $eligibleAds->isNotEmpty() ? $eligibleAds->random() : null;

    if ($ad) {
        // Simple impression counter — incremented once per render. Good
        // enough for this platform's scale without needing a separate
        // dedupe/analytics pipeline.
        $ad->increment('impressions_served');
    }
@endphp

@if ($ad)
    <a
        href="{{ route('ads.click', $ad) }}"
        target="_blank"
        rel="noopener sponsored"
        {{ $attributes->merge(['class' => 'glass-card block overflow-hidden group']) }}
    >
        <div class="relative">
            <img src="{{ $ad->displayImageUrl() }}" alt="{{ $ad->title }}" class="w-full {{ $placement === 'banner' ? 'h-24 sm:h-32' : 'h-32' }} object-cover transition group-hover:scale-[1.02]">
            <span class="absolute top-1.5 right-1.5 badge bg-black/50 text-white/70 text-[10px]">Ad</span>
        </div>
        <p class="p-3 text-sm font-medium truncate">{{ $ad->title }}</p>
    </a>
@endif
