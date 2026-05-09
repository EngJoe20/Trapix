@extends('layouts.app')

@section('title', 'File Security Analyzer')

@section('content')

<section class="min-h-[80vh] flex items-center justify-center px-6">

    <div class="max-w-4xl w-full text-center space-y-10">

        <!-- Title -->
        <div>
            <h1 class="text-4xl md:text-6xl font-bold">
                Trapix
                <span class="text-red-500">Security Analyzer</span>
            </h1>

            <p class="mt-4 text-heading-3 text-lg">
                Upload a file and detect malware, suspicious behavior & risk score instantly.
            </p>
        </div>

        <!-- Upload Box -->
        <div class="bg-box-bg border border-box-border rounded-2xl p-8 shadow-xl">

            <form class="space-y-4">

                <input type="file"
                       class="w-full p-3 rounded-lg bg-transparent border border-box-border">

                <button class="w-full bg-primary text-white py-3 rounded-lg hover:opacity-90">
                    Scan File
                </button>

            </form>

        </div>

        <!-- Features -->
        <div class="grid md:grid-cols-3 gap-6">

            <div class="p-6 rounded-xl border border-box-border bg-box-bg">
                <h3 class="text-heading-1 font-bold">Static Scan</h3>
                <p class="text-heading-3 mt-2">Signature detection</p>
            </div>

            <div class="p-6 rounded-xl border border-box-border bg-box-bg">
                <h3 class="text-heading-1 font-bold">Behavior Analysis</h3>
                <p class="text-heading-3 mt-2">Runtime simulation</p>
            </div>

            <div class="p-6 rounded-xl border border-box-border bg-box-bg">
                <h3 class="text-heading-1 font-bold">Risk Score</h3>
                <p class="text-heading-3 mt-2">AI threat evaluation</p>
            </div>

        </div>

    </div>

</section>

@endsection