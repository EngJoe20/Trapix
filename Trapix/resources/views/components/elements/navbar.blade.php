@php
$navItems = [
    ['href' => '#', 'text' => 'Home'],
    ['href' => '#services', 'text' => 'Services'],
    ['href' => '#about-us', 'text' => 'About us'],
    ['href' => '#features', 'text' => 'Features'],
];
@endphp

<header class="absolute inset-x-0 top-0 z-50 py-6">

    <x-shared.container>

        <nav class="w-full flex justify-between gap-6 relative">

            <!-- Logo -->
            <div class="min-w-max inline-flex relative">

                <a href="/" class="relative flex items-center gap-3">

                    <div class="relative w-7 h-7 overflow-hidden flex rounded-xl">

                        <span class="absolute w-4 h-4 -top-1 -right-1 bg-green-500 rounded-md rotate-45"></span>
                        <span class="absolute w-4 h-4 -bottom-1 -right-1 bg-[#FCDC58] rounded-md rotate-45"></span>
                        <span class="absolute w-4 h-4 -bottom-1 -left-1 bg-primary rounded-md rotate-45"></span>
                        <span class="absolute w-2 h-2 rounded-full bg-heading-1 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></span>

                    </div>

                    <div class="inline-flex text-lg font-semibold text-heading-1">
                        AgenceX
                    </div>

                </a>

            </div>

            <!-- overlay -->
            <div data-nav-overlay aria-hidden="true"
                 class="fixed hidden inset-0 lg:!hidden bg-box-bg bg-opacity-50 backdrop-filter backdrop-blur-xl"></div>

            <!-- menu -->
            <div data-navbar
                 class="flex h-0 overflow-hidden lg:!h-auto lg:scale-y-100 duration-300 ease-linear flex-col gap-y-6 gap-x-4 lg:flex-row w-full lg:justify-between lg:items-center absolute lg:relative top-full lg:top-0 bg-body lg:bg-transparent border-x border-x-box-border lg:border-x-0">

                <ul class="border-t border-box-border lg:border-t-0 px-6 lg:px-0 pt-6 lg:pt-0 flex flex-col lg:flex-row gap-y-4 gap-x-3 text-lg text-heading-2 w-full lg:justify-center lg:items-center">

                    @foreach ($navItems as $item)
                        <x-shared.navitem :href="$item['href']" :text="$item['text']" />
                    @endforeach

                </ul>

                <div class="lg:min-w-max flex items-center sm:w-max w-full pb-6 lg:pb-0 border-b border-box-bg lg:border-0 px-6 lg:px-0">

                    <x-shared.btn-link
                        text="Get Started"
                        href="#cta"
                        className="flex justify-center w-full sm:w-max"
                        variant="primary"
                    />

                </div>

            </div>

            <!-- actions -->
            <div class="min-w-max flex items-center gap-x-3">

                <!-- theme switch -->
                <button data-switch-theme
                        class="outline-none flex relative text-heading-2 rounded-full p-2 lg:p-3 border border-box-border">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         class="w-6 h-6 dark:flex hidden">

                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75..." />

                    </svg>

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         class="w-6 h-6 dark:hidden">

                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25..." />

                    </svg>

                    <span class="sr-only">switch theme</span>

                </button>

                <!-- mobile toggle -->
                <button data-toggle-nav data-open-nav="false"
                        class="lg:hidden lg:invisible outline-none w-7 h-auto flex flex-col relative">

                    <span id="line1" class="w-6 h-0.5 rounded-full bg-heading-2"></span>
                    <span id="line2" class="w-6 mt-1 h-0.5 rounded-full bg-heading-2"></span>
                    <span id="line3" class="w-6 mt-1 h-0.5 rounded-full bg-heading-2"></span>

                    <span class="sr-only">toggle nav</span>

                </button>

            </div>

        </nav>

    </x-shared.container>

</header>