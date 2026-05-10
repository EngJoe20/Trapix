@php
    $footerNav1 = [
        ['itemText' => 'Scanner', 'itemLink' => route('analyze')],
        ['itemText' => 'Analysis Engine', 'itemLink' => route('docs') . '#engine'],
        ['itemText' => 'API Reference', 'itemLink' => route('docs') . '#api'],
        ['itemText' => 'Security Insights', 'itemLink' => route('docs') . '#insights'],
    ];
    $footerSupport = [
        ['itemText' => 'Pricing', 'itemLink' => route('pricing')],
        ['itemText' => 'Documentation', 'itemLink' => route('docs')],
        ['itemText' => 'FAQ', 'itemLink' => route('docs') . '#faq'],
        ['itemText' => 'Contact Support', 'itemLink' => '#'],
    ];
    $footerAccount = auth()->check() ? [
        ['itemText' => 'Dashboard', 'itemLink' => route('dashboard')],
        ['itemText' => 'Analysis History', 'itemLink' => route('dashboard') . '#history'],
        ['itemText' => 'AI Integrations', 'itemLink' => route('settings.ai-integrations')],
    ] : [
        ['itemText' => 'Login', 'itemLink' => route('login')],
        ['itemText' => 'Register', 'itemLink' => route('register')],
    ];
    $footerLegal = [
        ['itemText' => 'Privacy Policy', 'itemLink' => '#'],
        ['itemText' => 'Terms of Service', 'itemLink' => '#'],
        ['itemText' => 'Cookie Policy', 'itemLink' => '#'],
    ];
@endphp

<div class="mt-16"></div>

<footer
    class="relative bg-bg pt-28 rounded-t-3xl border-t border-box-border dark:bg-gradient-to-b dark:from-gray-900/50 dark:to-[#030712] dark:border-green-900/20">

    {{-- ── Corner glow blobs (dark mode) ── --}}
    <div class="dark:absolute hidden dark:flex right-0 top-0 h-full w-full justify-end pointer-events-none">
        <div class="w-36 h-36 flex rounded-xl relative blur-3xl opacity-20">
            <span class="absolute w-20 h-20 -top-2 -right-2 bg-green-500 rounded-md rotate-45"></span>
            <span class="absolute w-20 h-20 -bottom-2 -right-2 bg-emerald-500 rounded-md rotate-45"></span>
        </div>
    </div>

    <div class="dark:absolute hidden dark:flex left-0 bottom-0 h-full w-full items-end pointer-events-none">
        <div class="w-36 h-36 flex rounded-xl relative blur-3xl opacity-20">
            <span class="absolute w-20 h-20 -top-2 -right-2 bg-green-500 rounded-md rotate-45"></span>
            <span class="absolute w-20 h-20 -bottom-2 -right-2 bg-emerald-500 rounded-md rotate-45"></span>
        </div>
    </div>

    <x-shared.container className="pb-12 relative overflow-auto">

        {{-- Top decorative line (dark mode) --}}
        <div class="dark:absolute hidden dark:block top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-green-500/50 to-transparent"></div>

        <div class="grid grid-cols-2 lg:grid-cols-3 lg:items-stretch gap-10 lg:gap-12 relative">

            {{-- ── Logo + description ── --}}
            <div class="col-span-2 lg:col-span-1 h-auto flex flex-col">

                <div class="h-full">

                    <a href="/" class="relative flex items-center gap-3 group">

                        {{-- Trapix logo image --}}
                        <img
                            src="{{ asset('images/logo.png') }}"
                            alt="Trapix"
                            class="h-14 w-auto object-contain
                                   transition-all duration-300
                                   drop-shadow-[0_0_8px_rgba(0,220,0,0.3)]
                                   group-hover:drop-shadow-[0_0_18px_rgba(0,220,0,0.7)]
                                   group-hover:scale-105"
                        />

                    </a>

                    <x-shared.paragraph className="mt-6 text-body">
                        Advanced AI-powered malware detection and security analysis platform.
                        Protecting digital assets with cutting-edge technology.
                    </x-shared.paragraph>

                </div>

                {{-- Social links --}}
                <div class="flex items-center gap-4 mt-8">
                    <a href="https://github.com/EngJoe20/Trapix" target="_blank"
                        class="w-10 h-10 rounded-lg bg-box-bg/50 border border-box-border flex items-center justify-center
                               text-heading-3 hover:text-green-400 hover:border-green-500/50 dark:hover:bg-green-900/30
                               transition-all duration-300 hover:scale-110">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0016 8c0-4.42-3.58-8-8-8z"/>
                        </svg>
                    </a>
                </div>

            </div>

            {{-- Nav groups --}}
            <x-blocks.group-footer-nav>
                <x-shared.footer-nav title="Account" :navItems="$footerAccount" />
                <x-shared.footer-nav title="Solutions" :navItems="$footerNav1" />
            </x-blocks.group-footer-nav>

            <x-blocks.group-footer-nav>
                <x-shared.footer-nav title="Support" :navItems="$footerSupport" />
                <x-shared.footer-nav title="Resources" :navItems="$footerLegal" />
            </x-blocks.group-footer-nav>

        </div>

    </x-shared.container>

    {{-- ── Bottom bar ── --}}
    <div class="relative bg-bg/50 backdrop-blur-md border-t border-box-border">

        <x-shared.container>

            <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-body/60 py-4">

                <div class="flex items-center gap-2">
                    <span class="text-green-400">●</span>
                    &copy; <span id="year"></span> Trapix Security. All rights reserved.
                </div>

                <div class="flex items-center gap-4">
                    <span>Proudly made by</span>
                    <a href="https://github.com/EngJoe20/Trapix" target="_blank"
                        class="font-semibold text-green-400 hover:text-green-300 transition-colors">
                        Trapix team
                    </a>
                </div>

            </div>

        </x-shared.container>

    </div>

</footer>