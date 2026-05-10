@props(['href' => '#'])

{{--
    x-auth-social-button
    A glass-style social OAuth button with a right arrow.
    Usage:
        <x-auth-social-button href="{{ route('auth.google') }}">
            <x-slot name="icon">…svg…</x-slot>
            Continue with Google
        </x-auth-social-button>
--}}

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'auth-social-btn']) }}>
    @isset($icon)
        <span class="mr-3 flex items-center" aria-hidden="true">
            {{ $icon }}
        </span>
    @endisset

    {{ $slot }}

    <span class="s-arrow" aria-hidden="true">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 5l7 7-7 7"/>
        </svg>
    </span>
</a>