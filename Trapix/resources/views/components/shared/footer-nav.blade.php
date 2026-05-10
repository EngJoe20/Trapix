@props([
    'title',
    'navItems' => []
])

<nav class="space-y-6">

    <h2 class="capitalize font-semibold text-gray-200 text-lg tracking-wide">
        {{ $title }}
    </h2>

    <ul class="space-y-3 text-sm font-medium">

        @foreach ($navItems as $navItem)

            <li>
                <a href="{{ $navItem['itemLink'] }}"
                   class="text-gray-400 hover:text-green-400 transition-colors duration-200 relative inline-block group">

                    {{ $navItem['itemText'] }}

                    <!-- Underline animation -->
                    <span class="absolute -bottom-0.5 left-0 w-0 h-0.5 bg-green-400 transition-all duration-300 group-hover:w-full"></span>

                </a>
            </li>

        @endforeach

    </ul>

</nav>