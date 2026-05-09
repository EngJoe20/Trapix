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
    ['itemText' => 'Parteners', 'itemLink' => '#'],
    ['itemText' => 'Jobs', 'itemLink' => '#'],
];

$footerLegal = [
    ['itemText' => 'Claim', 'itemLink' => '/#'],
    ['itemText' => 'Privacy', 'itemLink' => '#'],
    ['itemText' => 'Terms', 'itemLink' => '#'],
];
@endphp

<div class="mt-16"></div>

<footer class="relative bg-gradient-to-tr from-gray-100 to-gray-200 dark:from-gray-900 dark:to-transparent pt-28 rounded-t-3xl">

    <!-- decorations -->
    <div class="absolute right-0 top-0 h-full w-full flex justify-end">
        <div class="w-28 h-28 overflow-auto flex rounded-xl relative blur-2xl">
            <span class="absolute w-16 h-16 -top-1 -right-1 bg-green-500 rounded-md rotate-45"></span>
            <span class="absolute w-16 h-16 -bottom-1 -right-1 bg-[#FCDC58] rounded-md rotate-45"></span>
            <span class="absolute w-16 h-16 -bottom-1 -left-1 bg-primary rounded-md rotate-45"></span>
        </div>
    </div>

    <div class="absolute left-0 bottom-0 h-full w-full flex items-end">
        <div class="w-28 h-28 overflow-auto flex rounded-xl relative blur-2xl">
            <span class="absolute w-16 h-16 -top-1 -right-1 bg-green-500 rounded-md rotate-45"></span>
            <span class="absolute w-16 h-16 -bottom-1 -right-1 bg-[#FCDC58] rounded-md rotate-45"></span>
            <span class="absolute w-16 h-16 -bottom-1 -left-1 bg-primary rounded-md rotate-45"></span>
        </div>
    </div>

    <x-shared.container className="pb-8 relative overflow-auto">

        <span class="absolute top-1/2 left-1/2 -translate-y-1/2 -translate-x-1/2 blur-2xl opacity-20 w-24 h-16 sm:w-48 sm:h-36 rounded-full rotate-12 skew-x-6 bg-primary"></span>

        <div class="grid grid-cols-2 lg:grid-cols-3 lg:items-stretch gap-8 relative">

            <!-- Logo + description -->
            <div class="col-span-2 lg:col-span-1 h-auto flex flex-col">

                <div class="h-full">

                    <a href="#" class="relative flex items-center gap-3">

                        <div class="relative w-7 h-7 overflow-auto flex rounded-xl">
                            <span class="absolute w-4 h-4 -top-1 -right-1 bg-green-500 rounded-md rotate-45"></span>
                            <span class="absolute w-4 h-4 -bottom-1 -right-1 bg-[#FCDC58] rounded-md rotate-45"></span>
                            <span class="absolute w-4 h-4 -bottom-1 -left-1 bg-primary rounded-md rotate-45"></span>
                            <span class="absolute w-2 h-2 rounded-full bg-heading-1 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2"></span>
                        </div>

                        <div class="inline-flex text-lg font-semibold text-heading-1">
                            AgenceX
                        </div>

                    </a>

                    <x-shared.paragraph className="mt-8">
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Beatae, maiores nam doloribus id magni
                    </x-shared.paragraph>

                </div>

                <!-- social -->
                <div class="min-h-max flex items-center gap-4 text-heading-3 mt-8">

                    @foreach([
                        'facebook' => 'M16 8.049c0-4.446...',
                        'linkedin' => 'M0 1.146C0 .513...',
                        'twitter' => 'M5.026 15c6.038...',
                        'github' => 'M8 0C3.58 0 0 3.58...'
                    ] as $icon => $path)

                        <a href="#" class="transition hover:text-heading-1 hover:scale-105">

                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 16 16">
                                <path d="{{ $path }}" />
                            </svg>

                            <span class="sr-only">social link</span>

                        </a>

                    @endforeach

                </div>

            </div>

            <!-- Nav groups -->
            <x-blocks.group-footer-nav>
                <x-shared.footer-nav title="Company" :navItems="$footerCompany" />
                <x-shared.footer-nav title="Solutions" :navItems="$footerNav1" />
            </x-blocks.group-footer-nav>

            <x-blocks.group-footer-nav>
                <x-shared.footer-nav title="Support" :navItems="$footerSupport" />
                <x-shared.footer-nav title="Ressources" :navItems="$footerLegal" />
            </x-blocks.group-footer-nav>

        </div>

    </x-shared.container>

    <!-- bottom bar -->
    <div class="bg-gradient-to-tl from-box-bg py-2 relative">

        <x-shared.container>

            <div class="flex justify-between items-center gap-6 md:text-lg text-heading-3">

                <div>
                    &copy; <span id="year"></span> AgenceX. All right reserved
                </div>

                <div>
                    Proudly made by
                    <a href="https://github.com/johnkat-mj" target="_blank" class="font-semibold">John Kat</a>
                    • Distributed by
                    <a href="https://themewagon.com" target="_blank" class="font-semibold">ThemeWagon</a>
                </div>

            </div>

        </x-shared.container>

    </div>

</footer>