<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Trapix - Secure Access</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-950 text-white flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-primary">Trapix Security</h1>
            <p class="text-gray-400 text-sm">Secure login to analysis dashboard</p>
        </div>

        <div class="bg-box-bg border border-box-border p-6 rounded-2xl shadow-xl">
            {{ $slot }}
        </div>
    </div>

</body>
</html>
