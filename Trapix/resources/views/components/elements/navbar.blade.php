@php
    $navItems = [
        ['href' => route('home'), 'text' => 'Home'],
        ['href' => route('home') . '#features', 'text' => 'Features'],
        ['href' => route('analyze'), 'text' => 'Scanner'],
        ['href' => route('docs'), 'text' => 'Docs'],
        ['href' => route('home') . '#pricing', 'text' => 'Pricing'],
    ];
@endphp

<header class="fixed inset-x-0 top-0 z-50 py-4 backdrop-blur-xl transition-all duration-300
    bg-bg/80 border-b border-box-border">

    <x-shared.container>

        <nav class="w-full flex justify-between gap-6 relative items-center">

            <!-- Logo -->
                <div class="min-w-max inline-flex relative group">
                    <a href="/" class="relative flex items-center gap-3">
                        <div class="relative">
                            {{-- Animated glow ring behind logo --}}
                            <div class="absolute inset-0 rounded-xl bg-green-500/20 blur-md animate-pulse"></div>
                            <img
                              src="{{ asset('images/logo.png') }}"
                              alt="Trapix"
                              class="h-20 w-auto object-contain relative z-10
                                     transition-all duration-300
                                    group-hover:drop-shadow-[0_0_12px_rgba(0,220,0,0.7)]"/>
                        </div>
                    </a>
                </div>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex items-center gap-8">
                <ul class="flex gap-6 text-sm font-medium">
                    @foreach ($navItems as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="relative text-heading-2 hover:text-primary transition-colors duration-300 group">
                                {{ $item['text'] }}
                                <span
                                    class="absolute -bottom-1 left-0 w-0 h-0.5 bg-primary transition-all duration-300 group-hover:w-full"></span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="flex items-center gap-3 ml-4">
                    <!-- Theme Toggle -->
                    <button data-switch-theme class="outline-none flex relative p-2 rounded-full border transition-all duration-300
                                   border-box-border text-heading-2 hover:border-primary hover:text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5 dark:hidden">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5 dark:flex hidden">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>

                    @auth
                        <!-- Dashboard Button (Desktop) -->
                        <a href="{{ url('/dashboard') }}" class="hidden md:inline-flex items-center gap-2 btn-secondary">
                            <span>Dashboard</span>
                        </a>

                        <!-- Logout Button (Desktop) -->
                        <form method="POST" action="{{ route('logout') }}" class="hidden md:inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 btn-primary !from-red-600 !to-red-500">
                                <span>Logout</span>
                            </button>
                        </form>
                    @else
                        <!-- Login Button (Desktop) -->
                        <a href="{{ route('login') }}" class="hidden md:inline-flex items-center gap-2 btn-secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>Login</span>
                        </a>

                        <!-- Register Button (Desktop) -->
                        <a href="{{ route('register') }}" class="hidden md:inline-flex items-center gap-2 btn-primary">
                            <span>Register</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Mobile Actions (visible on mobile only) -->
            <div class="lg:hidden flex items-center gap-3">
                <!-- Mobile Scan Button (small) -->
                <a href="#cta" class="inline-flex items-center gap-1 btn-primary !px-3 !py-2 !text-xs">
                    <span>Scan</span>
                </a>

                <!-- Theme Toggle Mobile -->
                <button data-switch-theme
                    class="outline-none flex p-2 rounded-full border border-box-border text-heading-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5 dark:flex hidden">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5 dark:hidden">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </button>

                <!-- Hamburger Menu -->
                <button data-toggle-nav data-open-nav="false"
                    class="lg:hidden outline-none relative w-10 h-10 flex flex-col items-center justify-center
                               bg-cyan-900/30 border border-cyan-500/30 rounded-lg hover:border-cyan-400 transition-colors">

                    <span id="line1"
                        class="w-6 h-0.5 rounded-full bg-cyan-400 transition-all duration-300 ease-linear mb-1.5"></span>
                    <span id="line2"
                        class="w-6 h-0.5 rounded-full bg-cyan-400 transition-all duration-300 ease-linear mb-1.5"></span>
                    <span id="line3"
                        class="w-6 h-0.5 rounded-full bg-cyan-400 transition-all duration-300 ease-linear"></span>

                    <span class="sr-only">toggle nav</span>
                </button>
            </div>

        </nav>

        <!-- Mobile Nav Overlay & Dropdown -->
        <div data-nav-overlay aria-hidden="true"
            class="fixed hidden inset-0 lg:!hidden bg-box-bg/95 backdrop-blur-xl z-40"></div>

        <div data-navbar class="flex h-0 overflow-hidden lg:hidden duration-300 ease-linear
                    flex-col gap-y-6 gap-x-4 w-full
                    absolute top-full left-0 bg-bg/98 backdrop-blur-xl
                    border-x border-x-box-border border-t border-t-cyan-900/30 z-50">

            <!-- Mobile Nav Links -->
            <ul class="border-t border-box-border px-6 pt-6
                       flex flex-col gap-y-4 gap-x-3 text-lg text-heading-2
                       w-full">

                @foreach ($navItems as $item)
                    <li>
                        <a href="{{ $item['href'] }}"
                            class="block py-2 text-heading-2 hover:text-primary transition-colors">
                            {{ $item['text'] }}
                        </a>
                    </li>
                @endforeach

                @auth
                    <!-- Mobile Dashboard -->
                    <li class="pt-2 border-t border-box-border mt-2">
                        <a href="{{ url('/dashboard') }}" class="flex items-center gap-2 py-2 text-heading-2 hover:text-primary transition-colors">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full btn-primary !py-2">
                                <span>Logout</span>
                            </button>
                        </form>
                    </li>
                @else
                    <!-- Mobile Login/Register -->
                    <li class="pt-2 border-t border-box-border mt-2">
                        <a href="{{ route('login') }}" class="flex items-center gap-2 py-2 text-heading-2 hover:text-primary transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Login
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('register') }}" class="w-full btn-primary !py-2">
                            <span>Register</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </a>
                    </li>
                @endauth
            </ul>

            <!-- Mobile CTA -->
            <div
                class="flex items-center sm:w-max w-full pb-6 border-b border-box-bg px-6">
                <x-shared.btn-link text="Get Started" href="#cta" className="flex justify-center w-full sm:w-max"
                    variant="primary" />
            </div>
        </div>

    </x-shared.container>

</header>