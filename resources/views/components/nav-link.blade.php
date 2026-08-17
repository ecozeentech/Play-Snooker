@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 rounded-full text-sm font-medium text-gold-300 bg-white/10 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-3 py-2 rounded-full text-sm font-medium text-baize-100/70 hover:text-white hover:bg-white/5 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
