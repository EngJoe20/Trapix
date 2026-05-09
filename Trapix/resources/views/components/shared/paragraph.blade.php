@props([
    'className' => ''
])

<p {{ $attributes->merge([
    'class' => "md:text-lg text-heading-3 {$className}"
]) }}>
    {{ $slot }}
</p>
