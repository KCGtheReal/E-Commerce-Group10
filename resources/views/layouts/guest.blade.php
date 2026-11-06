<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? "{$title} — RESET" : 'RESET — Modern Minimalist Streetwear Essentials' }}</title>
    <meta name="description" content="RESET Studio account and authentication portal.">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,300..700;1,300..700&family=Noto+Sans+Khmer:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Immediate Theme Initialization Script -->
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
</head>
<body class="h-full bg-brand-offwhite text-brand-black dark:bg-[#0D0D0D] dark:text-neutral-100 antialiased selection:bg-brand-black selection:text-white flex flex-col font-sans transition-colors duration-200">
    <!-- Top Announcement Bar with Back & Toggles -->
    <aside aria-label="Announcement" class="bg-brand-black text-brand-bone text-xs py-2 px-4 border-b border-brand-dark/40">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('home') }}" class="font-medium tracking-wide truncate hover:text-white transition flex items-center gap-2">
                <span>←</span>
                <span>{{ __('store.nav_home') }}</span>
            </a>
            <div class="flex items-center space-x-3 sm:space-x-4 pl-4 shrink-0 font-display">
                <!-- Theme Switcher (Dark / Light) -->
                <button
                    type="button"
                    onclick="toggleTheme()"
                    aria-label="Toggle dark/light mode"
                    class="flex items-center space-x-1.5 px-1.5 py-0.5 rounded text-neutral-400 hover:text-white transition cursor-pointer"
                    title="Toggle Dark / Light Mode"
                >
                    <svg class="w-3.5 h-3.5 hidden dark:block text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-3.5 h-3.5 block dark:hidden text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <span class="text-[10px] tracking-wider hidden sm:inline">
                        <span class="dark:hidden">LIGHT</span>
                        <span class="hidden dark:inline">DARK</span>
                    </span>
                </button>
                <span class="text-neutral-700">|</span>

                <!-- Currency Toggle -->
                <div class="flex items-center space-x-1">
                    <span class="text-neutral-500 text-[10px] uppercase tracking-wider hidden sm:inline">{{ __('store.currency_switch') }}:</span>
                    <a href="{{ route('currency.set', 'KHR') }}" class="px-1.5 py-0.5 rounded transition {{ \App\Services\CurrencyService::current() === 'KHR' ? 'text-brand-accent font-bold' : 'text-neutral-400 hover:text-white' }}">៛ KHR</a>
                    <span class="text-neutral-600">|</span>
                    <a href="{{ route('currency.set', 'USD') }}" class="px-1.5 py-0.5 rounded transition {{ \App\Services\CurrencyService::current() === 'USD' ? 'text-brand-accent font-bold' : 'text-neutral-400 hover:text-white' }}">$ USD</a>
                </div>

                <!-- Language Toggle -->
                <div class="flex items-center space-x-1 border-l border-neutral-800 pl-3">
                    <span class="text-neutral-500 text-[10px] uppercase tracking-wider hidden sm:inline">{{ __('store.language_switch') }}:</span>
                    <a href="{{ route('locale.set', 'en') }}" class="px-1.5 py-0.5 rounded transition {{ app()->getLocale() === 'en' ? 'text-white font-bold underline underline-offset-4 decoration-brand-accent' : 'text-neutral-400 hover:text-white' }}">EN</a>
                    <span class="text-neutral-600">|</span>
                    <a href="{{ route('locale.set', 'km') }}" class="px-1.5 py-0.5 rounded transition {{ app()->getLocale() === 'km' ? 'text-white font-bold underline underline-offset-4 decoration-brand-accent' : 'text-neutral-400 hover:text-white' }}">ខ្មែរ</a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="inline-block transition hover:opacity-80" aria-label="RESET Home">
                    <x-application-logo class="h-8 w-auto mx-auto text-brand-black dark:text-white transition-colors" />
                </a>
            </div>

            <div class="w-full bg-white dark:bg-[#171717] border border-black/10 dark:border-neutral-800 p-6 sm:p-10 shadow-xs transition-colors duration-200">
                {{ $slot }}
            </div>

            <div class="mt-6 text-center text-xs text-brand-black/50 dark:text-neutral-500">
                <span>© {{ date('Y') }} RESET Studio. Phnom Penh, Cambodia.</span>
            </div>
        </div>
    </main>
</body>
</html>
