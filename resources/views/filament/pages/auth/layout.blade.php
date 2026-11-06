@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <!-- Immediate Theme Initialization Script (matches Filament and Storefront) -->
    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (storedTheme === 'dark' || (!storedTheme && systemPrefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();

        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Storefront CSS Only (Do NOT include app.js to avoid duplicate Alpine.js instance) -->
    @vite(['resources/css/app.css'])

    <style>
        /* Base typography & page resets */
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif !important;
        }

        .fi-simple-page {
            max-width: 100% !important;
        }

        /* LIGHT MODE SPECIFIC STYLES */
        html:not(.dark),
        html:not(.dark) body {
            background-color: #FAF8F5 !important;
            color: #111111 !important;
        }

        html:not(.dark) #auth-card {
            background-color: #FFFFFF !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
        }

        html:not(.dark) #form label,
        html:not(.dark) #form label span,
        html:not(.dark) #form .fi-fo-field-wrp-label span {
            color: #111111 !important;
        }

        html:not(.dark) #form .fi-input-wrp {
            border-radius: 0 !important;
            border: 1px solid rgba(0, 0, 0, 0.2) !important;
            background-color: #ffffff !important;
            box-shadow: none !important;
        }

        html:not(.dark) #form .fi-input-wrp:focus-within {
            border-color: #111111 !important;
            box-shadow: 0 0 0 1px #111111 !important;
        }

        html:not(.dark) #form .fi-input-wrp input {
            color: #111111 !important;
            background-color: transparent !important;
        }

        html:not(.dark) #form .fi-checkbox-input {
            border-radius: 0 !important;
            border: 1px solid rgba(0, 0, 0, 0.3) !important;
            background-color: #ffffff !important;
            color: #111111 !important;
        }

        html:not(.dark) #form .fi-checkbox-input:checked {
            background-color: #111111 !important;
            border-color: #111111 !important;
        }

        html:not(.dark) #form .fi-btn-color-primary {
            background-color: #111111 !important;
            color: #ffffff !important;
        }

        html:not(.dark) #form .fi-btn-color-primary:hover {
            background-color: #262626 !important;
            color: #ffffff !important;
        }

        html:not(.dark) #form .fi-btn-color-primary span {
            color: #ffffff !important;
        }

        /* DARK MODE SPECIFIC STYLES */
        html.dark,
        html.dark body {
            background-color: #0D0D0D !important;
            color: #F5F4F0 !important;
        }

        html.dark #auth-card {
            background-color: #171717 !important;
            border-color: #262626 !important;
        }

        html.dark #auth-card .border-black\/10 {
            border-color: #262626 !important;
        }

        html.dark #auth-card h1 {
            color: #F5F4F0 !important;
        }

        html.dark #auth-card p {
            color: #A3A3A3 !important;
        }

        html.dark #form label,
        html.dark #form label span,
        html.dark #form .fi-fo-field-wrp-label span {
            color: #F5F4F0 !important;
        }

        html.dark #form .fi-input-wrp {
            border-radius: 0 !important;
            border: 1px solid #333333 !important;
            background-color: #1F1F1F !important;
            box-shadow: none !important;
        }

        html.dark #form .fi-input-wrp:focus-within {
            border-color: #E65100 !important;
            box-shadow: 0 0 0 1px #E65100 !important;
        }

        html.dark #form .fi-input-wrp input {
            color: #FFFFFF !important;
            background-color: transparent !important;
        }

        html.dark #form .fi-checkbox-input {
            border-radius: 0 !important;
            border: 1px solid #404040 !important;
            background-color: #1F1F1F !important;
            color: #E65100 !important;
        }

        html.dark #form .fi-checkbox-input:checked {
            background-color: #E65100 !important;
            border-color: #E65100 !important;
        }

        html.dark #form .fi-btn-color-primary {
            background-color: #FFFFFF !important;
            color: #111111 !important;
        }

        html.dark #form .fi-btn-color-primary:hover {
            background-color: #E5E5E5 !important;
            color: #111111 !important;
        }

        html.dark #form .fi-btn-color-primary span {
            color: #111111 !important;
        }

        html.dark #form .fi-icon-btn {
            color: #A3A3A3 !important;
        }

        html.dark #form .fi-icon-btn:hover {
            color: #FFFFFF !important;
        }

        /* SHARED LABEL TYPOGRAPHY */
        #form label.fi-fo-field-wrp-label > span,
        #form .fi-fo-field-wrp-label > span {
            font-family: 'Space Grotesk', system-ui, sans-serif !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.05em !important;
            text-transform: uppercase !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.25rem !important;
        }

        #form label.fi-fo-field-wrp-label sup,
        #form .fi-fo-field-wrp-label sup {
            color: #EA580C !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
        }

        #form .fi-fo-field-wrp-label:has(.fi-checkbox-input) span:last-child {
            font-family: 'Space Grotesk', system-ui, sans-serif !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
        }

        /* SHARED BUTTON STYLING */
        #form .fi-btn-color-primary {
            font-family: 'Space Grotesk', sans-serif !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            border-radius: 0 !important;
            padding-top: 0.875rem !important;
            padding-bottom: 0.875rem !important;
            box-shadow: none !important;
            width: 100% !important;
            transition: all 0.15s ease-in-out !important;
        }
    </style>

    <div class="min-h-screen bg-brand-offwhite text-brand-black dark:bg-[#0D0D0D] dark:text-neutral-100 flex flex-col font-sans antialiased selection:bg-brand-black selection:text-white transition-colors duration-200">
        <!-- Top Announcement Bar matching Storefront -->
        <aside aria-label="Admin Navigation" class="bg-brand-black text-brand-bone text-xs py-2 px-4 border-b border-brand-dark/40">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <a href="{{ url('/') }}" class="font-medium tracking-wide truncate hover:text-white transition flex items-center gap-2">
                    <span>←</span>
                    <span>{{ __('store.nav_home') }}</span>
                </a>
                <div class="flex items-center space-x-3 text-[11px] font-display uppercase tracking-widest text-neutral-400">
                    <!-- Theme Toggle Button -->
                    <button
                        type="button"
                        onclick="toggleTheme()"
                        aria-label="Toggle dark/light mode"
                        class="flex items-center space-x-1.5 px-2 py-0.5 rounded text-neutral-400 hover:text-white transition cursor-pointer"
                        title="Toggle Dark / Light Mode"
                    >
                        <svg class="w-3.5 h-3.5 hidden dark:block text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-3.5 h-3.5 block dark:hidden text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <span class="text-[10px] tracking-wider">
                            <span class="dark:hidden">LIGHT</span>
                            <span class="hidden dark:inline">DARK</span>
                        </span>
                    </button>
                    <span class="text-neutral-700">|</span>
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-white font-bold">RESET Control Portal</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <!-- Centered RESET Logo with Orange Dot -->
                <div class="text-center mb-8">
                    <a href="{{ url('/') }}" class="inline-block transition hover:opacity-80" aria-label="RESET Home">
                        <x-application-logo class="h-8 w-auto mx-auto text-brand-black dark:text-white" />
                    </a>
                </div>

                <!-- Card container -->
                <div id="auth-card" class="w-full p-6 sm:p-10 shadow-xs border transition-colors duration-200">
                    {{ $slot }}
                </div>

                <!-- Footer Links under Card -->
                <div class="mt-6 text-center text-xs text-brand-black/50 dark:text-neutral-500 space-y-2">
                    <div>
                        <a href="{{ url('/') }}" class="font-bold underline uppercase hover:text-brand-accent transition text-[11px] tracking-wider">
                            ← Return to Storefront
                        </a>
                    </div>
                    <div>
                        <span>© {{ date('Y') }} RESET Studio. Management Portal.</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-filament-panels::layout.base>
