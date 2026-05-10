{{--
    x-primary-button
    The solid bright-green submit / CTA button.
    Usage: <x-primary-button>{{ __('Log in') }}</x-primary-button>
--}}
 
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'auth-primary-btn']) }}>
    {{ $slot }}
</button>