@props(['value' => ''])

{{--
    x-input-label
    Renders a small uppercase label above an input.
--}}

<label {{ $attributes->merge(['class' => 'block mb-1.5 text-[0.70rem] font-medium uppercase tracking-widest text-muted-contrast']) }}>
    {{ $value ?? $slot }}
</label>
