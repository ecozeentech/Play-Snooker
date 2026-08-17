@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-input-dark min-h-[44px]']) }}>
