<x-guest-layout>
 
    {{-- ── Session Status ───────────────────────────────────── --}}
    <x-auth-session-status class="mb-5" :status="session('status')" />
 
    {{-- ── Heading ──────────────────────────────────────────── --}}
    <h1 class="text-[1.85rem] font-semibold tracking-tight text-center text-white mb-1">
        Welcome back
    </h1>
    <p class="text-center text-sm text-muted-contrast mb-8">
        Sign in to your account
    </p>
 
    {{-- ── Login Form ───────────────────────────────────────── --}}
    <form method="POST" action="{{ route('login') }}">
        @csrf
 
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
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>
 
        {{-- Password --}}
        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                id="password"
                type="password"
                name="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>
 
        {{-- Remember Me + Forgot Password --}}
        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    class="rounded border-white/20"
                />
                <span class="text-sm text-muted-contrast">{{ __('Remember me') }}</span>
            </label>
 
            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="text-sm text-muted-contrast hover:text-green-accent transition-colors duration-200 rounded focus:outline-none focus:ring-2 focus:ring-green-500/40"
                >
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>
 
        {{-- Primary Submit Button --}}
        <x-primary-button>
            {{ __('Log in') }}
        </x-primary-button>
 
    </form>
    
    {{-- ── Dev Quick Login ──────────────────────────────────── --}}
    <div class="mt-6 pt-5 border-t border-glass">
        <p class="text-[0.70rem] font-medium uppercase tracking-widest text-muted-contrast mb-2">
            Quick Login (Dev Only)
        </p>
        <button
            type="button"
            onclick="quickLogin()"
            class="auth-ghost-btn"
        >
            Login as Demo User
        </button>
    </div>
 
    {{-- ── Sign Up Footer ───────────────────────────────────── --}}
    <p class="mt-7 text-center text-sm text-muted-contrast">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-green-accent font-medium hover:underline">
            Sign up
        </a>
    </p>
 
    {{-- ── Dev Script ───────────────────────────────────────── --}}
    <script>
        function quickLogin() {
            document.getElementById('email').value    = 'user@trapix.com';
            document.getElementById('password').value = 'password';
        }
    </script>
 
</x-guest-layout>
 
