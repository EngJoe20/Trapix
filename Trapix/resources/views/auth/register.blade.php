<x-guest-layout>

    {{-- ── Heading ──────────────────────────────────────────── --}}
    <h1 class="text-[1.85rem] font-semibold tracking-tight text-center text-white mb-1">
        Create account
    </h1>
    <p class="text-center text-sm text-muted-contrast mb-8">
        Sign up to get started
    </p>

    {{-- ── Register Form ────────────────────────────────────── --}}
    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Name --}}
        <div class="mb-4">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input
                id="name"
                type="text"
                name="name"
                :value="old('name')"
                placeholder="John Doe"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        {{-- Email Address --}}
        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email')"
                placeholder="you@example.com"
                required
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div class="mb-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                id="password"
                type="password"
                name="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        {{-- Confirm Password --}}
        <div class="mb-6">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                placeholder="••••••••"
                required
                autocomplete="new-password"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        {{-- Submit --}}
        <x-primary-button>
            {{ __('Create Account') }}
        </x-primary-button>

    </form>

    {{-- ── Already Registered ───────────────────────────────── --}}
    <p class="mt-7 text-center text-sm text-muted-contrast">
        Already have an account?
        <a
            href="{{ route('login') }}"
            class="text-green-accent font-medium hover:underline focus:outline-none focus:ring-2 focus:ring-green-500/40 rounded"
        >
            {{ __('Sign in') }}
        </a>
    </p>

</x-guest-layout>
