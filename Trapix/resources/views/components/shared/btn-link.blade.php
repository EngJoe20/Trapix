@props([
    'href',
    'text',
    'className' => '',
    'variant' => 'primary'
])

@php
    $themeClass = $variant === 'primary' ? 'btn-primary' : 'btn-secondary';
@endphp

<a href="{{ $href }}"
   class="{{ $themeClass }} {{ $className }} inline-flex items-center justify-center">
    <span>{{ $text }}</span>
    {{ $slot ?? '' }}
</a>
