@props(['messages' => []])

{{--
    x-input-error
    Shows validation error messages below an input.
--}}

@if ($messages && count((array) $messages) > 0)
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-0.5']) }}>
        @foreach ((array) $messages as $message)
            <li class="text-[0.76rem] text-red-400 pl-0.5">{{ $message }}</li>
        @endforeach
    </ul>
@endif
