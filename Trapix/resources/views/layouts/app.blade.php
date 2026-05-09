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

    <!-- Theme Switcher & Mobile Nav Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const switchTheme = document.querySelector("[data-switch-theme]");
            const toggleMenu = document.querySelector("[data-toggle-nav]");
            const navbar = document.querySelector("[data-navbar]");
            const overlayNav = document.querySelector("[data-nav-overlay]");

            // Theme switching
            if (switchTheme) {
                // Initialize theme on page load
                if (localStorage.getItem("appTheme") === "dark" ||
                    (!("appTheme" in localStorage) &&
                     window.matchMedia("(prefers-color-scheme: dark)").matches)) {
                    document.documentElement.classList.add("dark");
                } else {
                    document.documentElement.classList.remove("dark");
                }

                switchTheme.addEventListener("click", (e) => {
                    e.preventDefault();
                    const doc = document.documentElement;
                    if (doc) {
                        if (localStorage.getItem("appTheme")) {
                            if (localStorage.getItem("appTheme") === "light") {
                                doc.classList.add("dark");
                                localStorage.setItem("appTheme", "dark");
                            } else {
                                doc.classList.remove("dark");
                                localStorage.setItem("appTheme", "light");
                            }
                        } else {
                            if (doc.classList.contains("dark")) {
                                doc.classList.remove("dark");
                                localStorage.setItem("appTheme", "light");
                            } else {
                                doc.classList.add("dark");
                                localStorage.setItem("appTheme", "dark");
                            }
                        }
                    }
                });
            }

            // Mobile navigation toggle
            if (toggleMenu && navbar && overlayNav) {
                toggleMenu.addEventListener("click", function(e) {
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

    <!-- Year Update Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearElement = document.getElementById('year');
            if (yearElement) {
                yearElement.textContent = new Date().getFullYear();
            }
        });
    </script>
</body>
</html>