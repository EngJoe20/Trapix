@props([
    'title',
    'navItems' => []
])

<nav class="space-y-6">

    <h2 class="capitalze font-semibold text-heading-1 text-xl">
        {{ $title }}
    </h2>

    <ul class="space-y-3 font-medium md:text-lg text-heading-3">

        @foreach ($navItems as $navItem)

            <li>
                <a href="{{ $navItem['itemLink'] }}"
                   class="transition hover:text-primary">

                    {{ $navItem['itemText'] }}

                </a>
            </li>

        @endforeach

    </ul>

</nav>