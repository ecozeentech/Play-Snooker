<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-outline text-xs uppercase tracking-widest disabled:opacity-25']) }}>
    {{ $slot }}
</button>
