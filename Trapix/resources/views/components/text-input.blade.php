@props(['disabled' => false, 'id' => null])

{{--
    x-text-input
    Renders the glassmorphism input row with a green arrow button.
    Usage: <x-text-input id="email" type="email" name="email" :value="old('email')" />
--}}

<div class="auth-input-wrap">
    <input
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => 'auth-input'
        ]) !!}
    />

    {{-- Green arrow indicator --}}
    <div class="auth-input-arrow" aria-hidden="true" onclick="this.previousElementSibling.focus()">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24"
             stroke="#000000" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 5l7 7-7 7"/>
        </svg>
    </div>
</div>