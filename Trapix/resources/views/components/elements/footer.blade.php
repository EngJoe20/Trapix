@php
    $footerNav1 = [
        ['itemText' => 'Marketing', 'itemLink' => '#'],
        ['itemText' => 'Analytics', 'itemLink' => '#'],
        ['itemText' => 'Commerce', 'itemLink' => '#'],
        ['itemText' => 'Insights', 'itemLink' => '#'],
    ];

    $footerSupport = [
        ['itemText' => 'Pricing', 'itemLink' => '#'],
        ['itemText' => 'Guides', 'itemLink' => '#'],
        ['itemText' => 'FAQ', 'itemLink' => '#'],
        ['itemText' => 'Contact', 'itemLink' => '#'],
    ];

    $footerCompany = [
        ['itemText' => 'About', 'itemLink' => '/about'],
        ['itemText' => 'Blog', 'itemLink' => '#'],
        ['itemText' => 'Jobs', 'itemLink' => '#'],
        ['itemText' => 'Partners', 'itemLink' => '#'],
    ];

    $footerLegal = [
        ['itemText' => 'Claim', 'itemLink' => '/#'],
        ['itemText' => 'Privacy', 'itemLink' => '#'],
        ['itemText' => 'Terms', 'itemLink' => '#'],
    ];
@endphp

<div class="mt-16"></div>

<footer
    class="relative bg-bg pt-28 rounded-t-3xl border-t border-box-border dark:bg-gradient-to-b dark:from-gray-900/50 dark:to-[#030712] dark:border-cyan-900/20">

    <div class="dark:absolute hidden dark:flex right-0 top-0 h-full w-full justify-end pointer-events-none">
        <div class="w-36 h-36 flex rounded-xl relative blur-3xl opacity-20">
            <span class="absolute w-20 h-20 -top-2 -right-2 bg-cyan-500 rounded-md rotate-45"></span>
            <span class="absolute w-20 h-20 -bottom-2 -right-2 bg-emerald-500 rounded-md rotate-45"></span>
        </div>
    </div>

    <div class="dark:absolute hidden dark:flex left-0 bottom-0 h-full w-full items-end pointer-events-none">
        <div class="w-36 h-36 flex rounded-xl relative blur-3xl opacity-20">
            <span class="absolute w-20 h-20 -top-2 -right-2 bg-cyan-500 rounded-md rotate-45"></span>
            <span class="absolute w-20 h-20 -bottom-2 -right-2 bg-emerald-500 rounded-md rotate-45"></span>
        </div>
    </div>

    <x-shared.container className="pb-12 relative overflow-auto">

        <!-- Top decorative line (Dark mode only) -->
        <div
            class="dark:absolute hidden dark:block top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-cyan-500/50 to-transparent">
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 lg:items-stretch gap-10 lg:gap-12 relative">

            <!-- Logo + description -->
            <div class="col-span-2 lg:col-span-1 h-auto flex flex-col">

                <div class="h-full">

                    <a href="/" class="relative flex items-center gap-3 group">

                        <div
                            class="relative w-10 h-10 overflow-hidden flex rounded-lg items-center justify-center
                                    bg-primary/10 border border-primary/20 dark:bg-gradient-to-br dark:from-cyan-500/20 dark:to-emerald-500/20 dark:border-cyan-500/30
                                    group-hover:border-primary/50 dark:group-hover:border-cyan-400/50 transition-all duration-300">

                            <!-- Shield icon -->
                            <svg class="w-5 h-5 text-primary dark:text-cyan-400" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z" />
                            </svg>

                        </div>

                        <div
                            class="inline-flex text-xl font-bold tracking-wider text-heading-1 group-hover:text-primary transition-colors">
                            TRAPIX
                        </div>

                    </a>

                    <x-shared.paragraph className="mt-6 text-body">
                        Advanced AI-powered malware detection and security analysis platform.
                        Protecting digital assets with cutting-edge technology.
                    </x-shared.paragraph>

                </div>

                <!-- social links -->
                <div class="flex items-center gap-4 mt-8">

                    @php
                        $socials = [
                            'github' => [
                                'path' => 'M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0016 8c0-4.42-3.58-8-8-8z',
                                'label' => 'GitHub'
                            ],
                        ];
                    @endphp

                    <a href="https://github.com/johnkat-mj" target="_blank" class="w-10 h-10 rounded-lg bg-box-bg/50 border border-box-border flex items-center justify-center
                              text-heading-3 hover:text-primary hover:border-primary/50 dark:hover:bg-cyan-900/30
                               transition-all duration-300 hover:scale-110">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="{{ $socials['github']['path'] }}" />
                        </svg>
                    </a>
                </div>

            </div>

            <!-- Nav groups -->
            <x-blocks.group-footer-nav>
                <x-shared.footer-nav title="Company" :navItems="$footerCompany" />
                <x-shared.footer-nav title="Solutions" :navItems="$footerNav1" />
            </x-blocks.group-footer-nav>

            <x-blocks.group-footer-nav>
                <x-shared.footer-nav title="Support" :navItems="$footerSupport" />
                <x-shared.footer-nav title="Resources" :navItems="$footerLegal" />
            </x-blocks.group-footer-nav>

        </div>

    </x-shared.container>

    <!-- bottom bar -->
    <div class="relative bg-bg/50 backdrop-blur-md border-t border-box-border">

        <x-shared.container>

            <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-body/60 py-4">

                <div class="flex items-center gap-2">
                    <span class="text-primary dark:text-cyan-400">●</span>
                    &copy; <span id="year"></span> Trapix Security. All rights reserved.
                </div>

                <div class="flex items-center gap-4">
                    <span>Proudly made by</span>
                    <a href="https://github.com/johnkat-mj" target="_blank"
                        class="font-semibold text-primary dark:text-cyan-400 hover:text-primary/80 transition-colors">
                        Trapix team
                    </a>
                    <!-- <span class="text-box-border">•</span>
                    <span>Distributed by</span>
                    <a href="https://themewagon.com" target="_blank"
                        class="font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors">
                        ThemeWagon
                    </a> -->
                </div>

            </div>

        </x-shared.container>

    </div>

</footer>