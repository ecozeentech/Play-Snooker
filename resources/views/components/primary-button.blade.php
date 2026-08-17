<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-gold text-xs uppercase tracking-widest']) }}>
    {{ $slot }}
</button>
