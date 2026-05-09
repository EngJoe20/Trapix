<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Trapix Security Analyzer')</title>

    <meta name="description" content="Trapix - Malware & File Security Analysis Platform">

    <link rel="icon" href="{{ asset('favicon.svg') }}">

    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>

<body class="min-h-screen bg-bg text-heading-1 overflow-x-hidden">

    <!-- Navbar -->
    <x-elements.navbar />

    <!-- Main Content -->
    <main class="min-h-screen pt-24">
        @yield('content')
    </main>

    <!-- Footer -->
    <x-elements.footer />

</body>
</html>