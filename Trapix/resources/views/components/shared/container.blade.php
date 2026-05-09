@props([
    'className' => ''
])

<div {{ $attributes->merge([
    'class' => "mx-auto max-w-7xl w-full px-4 sm:px-6 lg:px-8 {$className}"
]) }}>
    {{ $slot }}
</div>
