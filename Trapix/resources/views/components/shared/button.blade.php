@props([
    'className' => '',
    'variant' => 'primary'
])

@php
    $themeClass = $variant === 'primary' ? 'btn-primary' : 'btn-secondary';
@endphp

<button {{ $attributes->merge([
    'class' => "{$themeClass} {$className} inline-flex items-center justify-center"
]) }}>
    {{ $slot }}
</button>
