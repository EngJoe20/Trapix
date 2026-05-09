@props([
    'className' => '',
    'variant' => 'primary'
])

@php
    $themeStyle = $variant === 'primary'
        ? 'bg-primary border-transparent relative after:bg-[#172554] hover:border-[#172554]'
        : 'text-primary';
@endphp

<button {{ $attributes->merge([
    'class' => "
        px-6 py-3 rounded-full outline-none relative overflow-hidden border
        duration-300 ease-linear
        after:absolute after:inset-x-0 after:aspect-square
        after:scale-0 after:opacity-70 after:origin-center
        after:duration-300 after:ease-linear after:rounded-full
        after:top-0 after:left-0 after:bg-[#172554]
        {$themeStyle}
        hover:after:opacity-100 hover:after:scale-[2.5]
        {$className}
    "
]) }}>

    {{ $slot }}

</button>