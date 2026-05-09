@props([
    'title',
    'description'
])

<div class="p-5 sm:p-6 lg:p-8 rounded-3xl border border-box-border bg-box-bg shadow-lg relative overflow-hidden">

    <div class="rounded-xl bg-gray-300 dark:bg-gray-950 p-3 text-heading-1 w-max relative">
        {!! $icon !!}
    </div>

    <div class="mt-6 space-y-4 relative">
        <h2 class="text-lg md:text-xl font-semibold text-heading-2">
            {{ $title }}
        </h2>

        <x-shared.paragraph>
            {{ $description }}
        </x-shared.paragraph>
    </div>

</div>
