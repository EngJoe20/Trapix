@extends('layouts.app')

@section('content')
    <main class="flex flex-col gap-y-20 md:gap-y-32 overflow-hidden">
        <x-sections.hero />
        <x-sections.brands />
        <!-- <x-sections.services /> -->
        <x-sections.about-us />
        <x-sections.features />
        <x-sections.cta />
    </main>
@endsection