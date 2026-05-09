<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Trapix Security Analyzer')</title>

    <meta name="description" content="Trapix - Advanced Malware & File Security Analysis Platform">

    <link rel="icon" href="{{ asset('favicon.svg') }}">

    <!-- Terminal/Coding Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&family=Rajdhani:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        if (localStorage.getItem('appTheme') === 'dark' || (!('appTheme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else if (localStorage.getItem('appTheme') === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            // Default to dark for cyber aesthetic if no system preference
            document.documentElement.classList.add('dark');
        }
    </script>

    <style>
        /* Glow effects for cyber theme */
        .neon-glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3),
                0 0 40px rgba(59, 130, 246, 0.1),
                inset 0 0 20px rgba(59, 130, 246, 0.05);
        }

        .neon-text {
            text-shadow: 0 0 10px rgba(59, 130, 246, 0.8),
                0 0 20px rgba(59, 130, 246, 0.5);
        }

        .scan-line {
            background: linear-gradient(to bottom,
                    transparent,
                    rgba(59, 130, 246, 0.1),
                    transparent);
            animation: scan 3s linear infinite;
        }

        @keyframes scan {
            0% {
                transform: translateY(-100%);
            }

            100% {
                transform: translateY(100vh);
            }
        }

        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 5px currentColor, 0 0 10px currentColor;
            }

            50% {
                box-shadow: 0 0 20px currentColor, 0 0 30px currentColor;
            }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.8s ease-out forwards;
        }

        @keyframes typing {
            from {
                width: 0
            }

            to {
                width: 100%
            }
        }

        @keyframes blink {

            0%,
            50% {
                border-color: transparent
            }

            51%,
            100% {
                border-color: #3b82f6
            }
        }

        .typing-cursor {
            border-right: 3px solid #3b82f6;
            animation: blink 1s step-end infinite;
        }
    </style>
</head>

<body class="min-h-screen bg-bg text-body-contrast overflow-x-hidden font-mono">

    <!-- Navbar -->
    <x-elements.navbar />

    <!-- Main Content -->
    <main class="min-h-screen pt-24">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    <!-- Footer -->
    <x-elements.footer />

    <!-- Theme Switcher & Mobile Nav Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const switchThemes = document.querySelectorAll("[data-switch-theme]");
            const toggleMenu = document.querySelector("[data-toggle-nav]");
            const navbar = document.querySelector("[data-navbar]");
            const overlayNav = document.querySelector("[data-nav-overlay]");

            // Theme switching
            if (switchThemes.length > 0) {
                switchThemes.forEach(switchTheme => {
                    switchTheme.addEventListener("click", (e) => {
                        e.preventDefault();
                        if (document.documentElement.classList.contains("dark")) {
                            document.documentElement.classList.remove("dark");
                            localStorage.setItem("appTheme", "light");
                        } else {
                            document.documentElement.classList.add("dark");
                            localStorage.setItem("appTheme", "dark");
                        }
                    });
                });
            }

            // Mobile navigation toggle
            if (toggleMenu && navbar && overlayNav) {
                toggleMenu.addEventListener("click", function (e) {
                    e.preventDefault();
                    if (toggleMenu.getAttribute("data-open-nav") === "false") {
                        toggleMenu.setAttribute("data-open-nav", "true");
                        overlayNav.setAttribute("data-is-visible", "true");
                        document.body.classList.add("!overflow-y-hidden");
                        navbar.style.height = `${navbar.scrollHeight}px`;
                    } else {
                        toggleMenu.setAttribute("data-open-nav", "false");
                        overlayNav.setAttribute("data-is-visible", "false");
                        document.body.classList.remove("!overflow-y-hidden");
                        navbar.style.height = "0px";
                    }
                });

                navbar.addEventListener("click", () => {
                    toggleMenu.setAttribute("data-open-nav", "false");
                    overlayNav.setAttribute("data-is-visible", "false");
                    document.body.classList.remove("!overflow-y-hidden");
                    navbar.style.height = "0px";
                });

                overlayNav.addEventListener("click", () => {
                    toggleMenu.setAttribute("data-open-nav", "false");
                    overlayNav.setAttribute("data-is-visible", "false");
                    document.body.classList.remove("!overflow-y-hidden");
                    navbar.style.height = "0px";
                });
            }
        });
    </script>

    <!-- Terminal typing effect for hero -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typewriter = document.querySelector('.typewriter-text');
            if (typewriter) {
                const text = typewriter.textContent;
                typewriter.textContent = '';
                typewriter.style.width = '0';
                let i = 0;

                function type() {
                    if (i < text.length) {
                        typewriter.textContent += text.charAt(i);
                        i++;
                        setTimeout(type, 50);
                    } else {
                        typewriter.classList.add('typing-cursor');
                    }
                }
                setTimeout(type, 1000);
            }

            // Year update
            const yearElement = document.getElementById('year');
            if (yearElement) {
                yearElement.textContent = new Date().getFullYear();
            }
        });
    </script>

    @stack('scripts')
</body>

</html>