<nav class="bg-body border-b border-box-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between h-16 items-center">

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                <span class="font-bold text-primary">Trapix</span>
            </a>

            <!-- Links -->
            <div class="hidden sm:flex gap-6 text-heading-3">
                <a href="{{ route('dashboard') }}" class="hover:text-primary">Dashboard</a>
                <a href="#" class="hover:text-primary">Scan File</a>
                <a href="#" class="hover:text-primary">Reports</a>
            </div>

            <!-- User -->
            <div class="text-sm text-gray-400">
                {{ Auth::user()->name }}
            </div>

        </div>

    </div>
</nav>