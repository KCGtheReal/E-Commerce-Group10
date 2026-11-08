<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? "{$title} — RESET" : 'RESET — Modern Minimalist Streetwear Essentials' }}</title>
    <meta name="description" content="{{ $description ?? 'RESET is an editorial clothing brand producing heavyweight, minimalist streetwear essentials in Phnom Penh, Cambodia. Pay securely with Bakong KHQR.' }}">
    
    <!-- Open Graph / Social Meta -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ isset($title) ? "{$title} — RESET" : 'RESET — Modern Minimalist Streetwear Essentials' }}">
    <meta property="og:description" content="{{ $description ?? 'Editorial, structural garments with generous cuts and uncompromising fabric weight. Designed in Phnom Penh.' }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-image.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Google Fonts: Space Grotesk (Latin Display) & Kantumruy Pro / Noto Sans Khmer (Khmer) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,300..700;1,300..700&family=Noto+Sans+Khmer:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        window.RESET_STORE = {
            currency: '{{ \App\Services\CurrencyService::current() }}',
            exchangeRate: {{ (int) \App\Models\Setting::get('exchange_rate', 4100) }},
        };
    </script>

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

    @stack('styles')
</head>
<body class="min-h-screen bg-brand-offwhite text-brand-black dark:bg-[#0D0D0D] dark:text-neutral-100 antialiased selection:bg-brand-black selection:text-white flex flex-col font-sans transition-colors duration-200" x-data="cartDrawer">
    
    <!-- Top Announcement Bar -->
    <aside aria-label="Announcement" class="bg-brand-black text-brand-bone text-xs py-2 px-3 sm:px-4 border-b border-brand-dark/40 overflow-x-hidden">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <p class="font-medium tracking-wide truncate max-w-[160px] xs:max-w-xs sm:max-w-none text-[11px] sm:text-xs">
                @if(app()->getLocale() === 'km')
                    {{ \App\Models\Setting::get('announcement_banner_km', 'ដឹកជញ្ជូនឥតគិតថ្លៃសម្រាប់ការបញ្ជាទិញចាប់ពី 200,000 ៛ ឡើងទៅ | ទូទាត់រហ័សជាមួយបាគង KHQR') }}
                @else
                    {{ \App\Models\Setting::get('announcement_banner_en', 'Free delivery on orders over 200,000 ៛ | Instant QR checkout with Bakong KHQR') }}
                @endif
            </p>
            <div class="flex items-center space-x-2 sm:space-x-4 pl-2 sm:pl-4 shrink-0 font-display">
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

    <!-- Main Navigation Bar -->
    <header class="sticky top-0 z-40 bg-brand-offwhite/95 dark:bg-[#0D0D0D]/95 backdrop-blur-md border-b border-brand-border dark:border-neutral-800 transition-colors duration-200">
        @php
            $currentCategorySlug = request('category');
            if (! $currentCategorySlug && isset($product) && $product->relationLoaded('category')) {
                $currentCategorySlug = $product->category?->slug;
            }

            $isTees = request()->routeIs('shop.*') && $currentCategorySlug === 'tees-and-tops';
            $isOuterwear = request()->routeIs('shop.*') && $currentCategorySlug === 'hoodies-and-outerwear';
            $isBottoms = request()->routeIs('shop.*') && $currentCategorySlug === 'pants-and-bottoms';
            $isShop = request()->routeIs('shop.*') && ! $isTees && ! $isOuterwear && ! $isBottoms;
            $isAbout = request()->routeIs('page.about');

            $activeNavClass = 'text-brand-accent font-bold after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-brand-accent';
            $inactiveNavClass = 'text-neutral-600 hover:text-brand-black';
        @endphp
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Mobile Menu Button -->
                <div class="flex items-center lg:hidden" x-data="{ mobileMenu: false }">
                    <button @click="mobileMenu = !mobileMenu" type="button" class="p-2 -ml-2 text-brand-black dark:text-white focus:outline-none" aria-label="Toggle navigation">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Mobile Drawer (Teleported to body to escape sticky header container) -->
                    <template x-teleport="body">
                        <div x-show="mobileMenu" 
                             x-cloak 
                             class="fixed inset-0 z-50 overflow-hidden lg:hidden" 
                             role="dialog" 
                             aria-modal="true" 
                             @keydown.window.escape="mobileMenu = false">
                            <!-- Backdrop Overlay with Fade Animation -->
                            <div @click="mobileMenu = false" 
                                 x-show="mobileMenu" 
                                 x-transition:enter="ease-in-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="ease-in-out duration-300"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 bg-brand-black/70 backdrop-blur-sm transition-opacity"></div>

                            <!-- Drawer Container with Left-to-Right Slide Animation -->
                            <div class="fixed inset-y-0 left-0 max-w-full flex pr-10">
                                <div x-show="mobileMenu"
                                     x-transition:enter="transform transition ease-out duration-300"
                                     x-transition:enter-start="-translate-x-full"
                                     x-transition:enter-end="translate-x-0"
                                     x-transition:leave="transform transition ease-in duration-250"
                                     x-transition:leave-start="translate-x-0"
                                     x-transition:leave-end="-translate-x-full"
                                     class="w-screen max-w-xs h-full min-h-screen bg-brand-offwhite dark:bg-[#121212] p-6 shadow-2xl flex flex-col justify-between overflow-y-auto text-brand-black dark:text-neutral-100 transition-colors z-10">
                                <div>
                                    <div class="flex items-center justify-between pb-6 border-b border-brand-border dark:border-neutral-800">
                                        <a href="{{ route('home') }}" @click="mobileMenu = false">
                                            <x-application-logo class="h-6 w-auto text-brand-black dark:text-white transition-colors" />
                                        </a>
                                        <button @click="mobileMenu = false" class="p-1.5 text-brand-black dark:text-white hover:text-brand-accent transition" aria-label="Close menu">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Mobile Drawer Search Button -->
                                    <div class="mt-4">
                                        <button @click="mobileMenu = false; $dispatch('open-search')" type="button" class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs bg-brand-bone dark:bg-neutral-800/90 border border-brand-border dark:border-neutral-700 rounded-xl text-neutral-500 dark:text-neutral-300 hover:text-brand-black dark:hover:text-white transition">
                                            <span class="flex items-center space-x-2">
                                                <svg class="h-4 w-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                                </svg>
                                                <span>{{ __('store.search_placeholder') }}</span>
                                            </span>
                                            <span class="text-[10px] font-mono text-neutral-400 uppercase font-bold">Search</span>
                                        </button>
                                    </div>

                                    <nav class="mt-5 flex flex-col space-y-3 text-lg font-display uppercase tracking-wider">
                                        <a href="{{ route('home') }}" @click="mobileMenu = false" class="py-1.5 hover:text-brand-accent transition {{ request()->routeIs('home') ? 'text-brand-accent font-bold' : 'text-brand-black dark:text-neutral-100' }}">{{ __('store.nav_home') }}</a>
                                        <a href="{{ route('shop.index') }}" @click="mobileMenu = false" class="py-1.5 hover:text-brand-accent transition {{ $isShop ? 'text-brand-accent font-bold' : 'text-brand-black dark:text-neutral-100' }}">{{ __('store.nav_shop') }}</a>
                                        <div class="pl-4 py-1 flex flex-col space-y-2 text-sm text-neutral-600 dark:text-neutral-400 border-l-2 border-brand-border dark:border-neutral-800">
                                            <a href="{{ route('shop.index', ['category' => 'tees-and-tops']) }}" @click="mobileMenu = false" class="py-1 hover:text-brand-accent transition {{ $isTees ? 'text-brand-accent font-bold' : '' }}">{{ __('store.nav_tees') }}</a>
                                            <a href="{{ route('shop.index', ['category' => 'hoodies-and-outerwear']) }}" @click="mobileMenu = false" class="py-1 hover:text-brand-accent transition {{ $isOuterwear ? 'text-brand-accent font-bold' : '' }}">{{ __('store.nav_outerwear') }}</a>
                                            <a href="{{ route('shop.index', ['category' => 'pants-and-bottoms']) }}" @click="mobileMenu = false" class="py-1 hover:text-brand-accent transition {{ $isBottoms ? 'text-brand-accent font-bold' : '' }}">{{ __('store.nav_bottoms') }}</a>
                                        </div>
                                        <a href="{{ route('page.about') }}" @click="mobileMenu = false" class="py-1.5 hover:text-brand-accent transition {{ $isAbout ? 'text-brand-accent font-bold' : 'text-brand-black dark:text-neutral-100' }}">{{ __('store.nav_about') }}</a>
                                        <a href="{{ route('page.faq') }}" @click="mobileMenu = false" class="py-1.5 hover:text-brand-accent transition {{ request()->routeIs('page.faq') ? 'text-brand-accent font-bold' : 'text-brand-black dark:text-neutral-100' }}">{{ __('store.nav_faq') }}</a>
                                        <a href="{{ route('page.contact') }}" @click="mobileMenu = false" class="py-1.5 hover:text-brand-accent transition {{ request()->routeIs('page.contact') ? 'text-brand-accent font-bold' : 'text-brand-black dark:text-neutral-100' }}">{{ __('store.nav_contact') }}</a>
                                    </nav>
                                </div>
                                <div class="pt-6 border-t border-brand-border dark:border-neutral-800 text-sm space-y-3">
                                    @auth
                                        @if(auth()->user()->isAdmin())
                                            <a href="/admin" class="block font-bold text-brand-accent hover:underline flex items-center justify-between">
                                                <span>{{ __('store.admin_panel') }}</span>
                                                <span class="text-[10px] bg-brand-accent/10 text-brand-accent px-2 py-0.5 rounded font-mono uppercase font-bold">Admin</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('account.orders') }}" class="block font-medium hover:text-brand-accent text-brand-black dark:text-neutral-100">{{ __('store.nav_orders') }}</a>
                                        <a href="{{ route('account.profile') }}" class="block font-medium hover:text-brand-accent text-brand-black dark:text-neutral-100">{{ __('store.account_profile') }}</a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="text-neutral-500 hover:text-brand-black dark:hover:text-white">{{ __('store.nav_logout') }}</button>
                                        </form>
                                    @else
                                        @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()?->isAdmin())
                                            <a href="/admin" class="block font-bold text-brand-accent hover:underline flex items-center justify-between">
                                                <span>{{ __('store.admin_panel') }}</span>
                                                <span class="text-[10px] bg-brand-accent/10 text-brand-accent px-2 py-0.5 rounded font-mono uppercase font-bold">Admin</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('login') }}" class="block font-bold text-brand-black dark:text-white hover:text-brand-accent">{{ __('store.nav_login') }}</a>
                                        <a href="{{ route('register') }}" class="block text-neutral-600 dark:text-neutral-400 hover:text-brand-black dark:hover:text-white">{{ __('store.nav_register') }}</a>
                                    @endauth

                                    <!-- Mobile Theme, Currency & Language Toggles -->
                                    <div class="pt-4 border-t border-brand-border/60 dark:border-neutral-800 text-xs space-y-3 font-display">
                                        <div class="flex items-center justify-between pb-2 border-b border-brand-border/40 dark:border-neutral-800/80">
                                            <span class="text-neutral-500 uppercase tracking-wider text-[11px]">Theme:</span>
                                            <button
                                                type="button"
                                                onclick="toggleTheme()"
                                                class="flex items-center space-x-1.5 px-2.5 py-1 rounded bg-brand-bone dark:bg-neutral-800 text-xs font-bold font-display uppercase tracking-wider text-brand-black dark:text-white cursor-pointer"
                                            >
                                                <span class="dark:hidden">☀️ Light</span>
                                                <span class="hidden dark:inline">🌙 Dark</span>
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-neutral-500 uppercase tracking-wider text-[11px]">{{ __('store.currency_switch') }}:</span>
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('currency.set', 'KHR') }}" class="px-2 py-1 rounded {{ \App\Services\CurrencyService::current() === 'KHR' ? 'bg-brand-black text-white dark:bg-white dark:text-brand-black font-bold' : 'text-neutral-600 dark:text-neutral-400 hover:text-black dark:hover:text-white' }}">៛ KHR</a>
                                                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                                                <a href="{{ route('currency.set', 'USD') }}" class="px-2 py-1 rounded {{ \App\Services\CurrencyService::current() === 'USD' ? 'bg-brand-black text-white dark:bg-white dark:text-brand-black font-bold' : 'text-neutral-600 dark:text-neutral-400 hover:text-black dark:hover:text-white' }}">$ USD</a>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-neutral-500 uppercase tracking-wider text-[11px]">{{ __('store.language_switch') }}:</span>
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('locale.set', 'en') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-brand-black text-white dark:bg-white dark:text-brand-black font-bold' : 'text-neutral-600 dark:text-neutral-400 hover:text-black dark:hover:text-white' }}">EN</a>
                                                <span class="text-neutral-300 dark:text-neutral-700">|</span>
                                                <a href="{{ route('locale.set', 'km') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'km' ? 'bg-brand-black text-white dark:bg-white dark:text-brand-black font-bold' : 'text-neutral-600 dark:text-neutral-400 hover:text-black dark:hover:text-white' }}">ខ្មែរ</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                </div>

                <!-- Wordmark Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="group flex items-center" aria-label="RESET Home">
                        <x-application-logo class="h-7 sm:h-8 w-auto text-brand-black dark:text-white transition-colors" />
                    </a>
                </div>

                <!-- Desktop Nav Links -->
                <nav class="hidden lg:flex items-center space-x-8 text-sm font-display uppercase tracking-widest font-semibold">
                    <a href="{{ route('shop.index') }}" class="transition relative py-1 {{ $isShop ? $activeNavClass : $inactiveNavClass }}">
                        {{ __('store.nav_shop') }}
                    </a>
                    <a href="{{ route('shop.index', ['category' => 'tees-and-tops']) }}" class="transition relative py-1 {{ $isTees ? $activeNavClass : $inactiveNavClass }}">
                        {{ __('store.nav_tees') }}
                    </a>
                    <a href="{{ route('shop.index', ['category' => 'hoodies-and-outerwear']) }}" class="transition relative py-1 {{ $isOuterwear ? $activeNavClass : $inactiveNavClass }}">
                        {{ __('store.nav_outerwear') }}
                    </a>
                    <a href="{{ route('shop.index', ['category' => 'pants-and-bottoms']) }}" class="transition relative py-1 {{ $isBottoms ? $activeNavClass : $inactiveNavClass }}">
                        {{ __('store.nav_bottoms') }}
                    </a>
                    <a href="{{ route('page.about') }}" class="transition relative py-1 {{ $isAbout ? $activeNavClass : $inactiveNavClass }}">
                        {{ __('store.nav_about') }}
                    </a>
                </nav>

                <!-- Header Actions -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Desktop Interactive Search Bar Trigger -->
                    <button @click="$dispatch('open-search')" type="button" class="group hidden sm:flex items-center justify-between w-48 md:w-56 pl-3 pr-2 py-1.5 text-xs bg-brand-bone hover:bg-neutral-200/70 border border-brand-border rounded-full text-neutral-500 hover:text-brand-black transition shadow-inner cursor-pointer" aria-label="Search products">
                        <span class="flex items-center space-x-2 truncate">
                            <svg class="h-3.5 w-3.5 text-neutral-400 group-hover:text-brand-black transition shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <span class="truncate">{{ request('q') ? request('q') : __('store.search_placeholder') }}</span>
                        </span>
                        <kbd class="hidden md:inline-flex items-center px-1.5 py-0.5 text-[10px] font-mono font-bold bg-white/90 border border-neutral-300 rounded text-neutral-400 group-hover:border-neutral-400 transition ml-2 shrink-0">⌘K</kbd>
                    </button>

                    <!-- Mobile Search Trigger Icon -->
                    <button @click="$dispatch('open-search')" type="button" class="sm:hidden p-2 text-brand-black hover:text-brand-accent transition cursor-pointer" aria-label="Open search">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </button>

                    <!-- Wishlist Icon -->
                    <a href="{{ route('wishlist.index') }}" class="p-2 text-brand-black hover:text-brand-accent transition relative" aria-label="Wishlist">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </a>

                    <!-- User Account Dropdown -->
                    <div class="relative" x-data="{ userMenu: false }" @click.away="userMenu = false">
                        <button @click="userMenu = !userMenu" type="button" class="p-2 text-brand-black hover:text-brand-accent transition flex items-center" aria-label="Account menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </button>
                        <div x-show="userMenu" x-cloak class="absolute right-0 mt-2 w-48 bg-white border border-brand-border shadow-xl rounded-md py-2 z-50">
                            @auth
                                <div class="px-4 py-2 border-b border-brand-border text-xs text-neutral-500 truncate uppercase">
                                    {{ auth()->user()->name }}
                                </div>
                                @if(auth()->user()->isAdmin())
                                    <a href="/admin" class="block px-4 py-2 text-xs font-bold text-brand-accent hover:bg-neutral-50">{{ __('store.admin_panel') }}</a>
                                @endif
                                <a href="{{ route('account.orders') }}" class="block px-4 py-2 text-xs text-brand-black hover:bg-neutral-50">{{ __('store.nav_orders') }}</a>
                                <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-xs text-brand-black hover:bg-neutral-50">{{ __('store.account_profile') }}</a>
                                <a href="{{ route('account.addresses') }}" class="block px-4 py-2 text-xs text-brand-black hover:bg-neutral-50">{{ __('store.account_addresses') }}</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-xs text-red-600 hover:bg-neutral-50 border-t border-brand-border mt-1">
                                        {{ __('store.nav_logout') }}
                                    </button>
                                </form>
                            @else
                                @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()?->isAdmin())
                                    <div class="px-4 py-1.5 border-b border-brand-border text-xs text-neutral-500 truncate">
                                        {{ auth()->guard('admin')->user()->name }}
                                    </div>
                                    <a href="/admin" class="block px-4 py-2 text-xs font-bold text-brand-accent hover:bg-neutral-50 border-b border-brand-border/40">{{ __('store.admin_panel') }}</a>
                                @endif
                                <a href="{{ route('login') }}" class="block px-4 py-2 text-xs font-bold text-brand-black hover:bg-neutral-50">{{ __('store.nav_login') }}</a>
                                <a href="{{ route('register') }}" class="block px-4 py-2 text-xs text-neutral-600 hover:bg-neutral-50">{{ __('store.nav_register') }}</a>
                            @endauth
                        </div>
                    </div>

                    <!-- Cart Trigger Button with Live Counter Badge -->
                    <button @click="open = true; fetchCart()" type="button" class="relative bg-brand-black text-white px-3.5 py-2 rounded-full flex items-center space-x-2 text-xs font-display uppercase tracking-wider hover:bg-brand-dark transition shadow-sm" aria-label="Open Cart">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        <span class="font-bold" x-text="itemCount">0</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Slide-over Cart Drawer (Alpine.js) -->
    <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <div @click="open = false" x-show="open" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-brand-black/60 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="open" x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="w-screen max-w-md bg-white shadow-2xl flex flex-col">
                
                <!-- Drawer Header -->
                <div class="p-6 border-b border-brand-border flex items-center justify-between bg-brand-card-bg">
                    <div>
                        <h2 class="text-lg font-display uppercase tracking-wider font-bold text-brand-black" id="slide-over-title">
                            {{ __('store.cart_drawer_title') }}
                        </h2>
                        <p class="text-xs text-neutral-500 mt-0.5" x-text="itemCount + ' ' + (itemCount === 1 ? '{{ __('store.item_singular') }}' : '{{ __('store.items_count') }}')"></p>
                    </div>
                    <button @click="open = false" type="button" class="p-2 text-neutral-400 hover:text-brand-black transition" aria-label="Close cart">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Free Shipping Progress Bar -->
                <div class="px-6 py-3 bg-neutral-50 border-b border-brand-border text-xs">
                    <template x-if="freeShippingQualified">
                        <p class="text-emerald-700 font-semibold flex items-center space-x-1.5">
                            <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <span>{{ __('store.cart_free_shipping_unlocked') }}</span>
                        </p>
                    </template>
                    <template x-if="!freeShippingQualified">
                        <div>
                            <p class="text-neutral-600" x-html="'{{ __('store.cart_free_shipping_hint', ['amount' => '<strong class=\'text-brand-black\'>:amount</strong>']) }}'.replace(':amount', freeShippingRemainingFormatted)">
                            </p>
                            <div class="w-full bg-neutral-200 h-1.5 rounded-full mt-2 overflow-hidden">
                                <div class="bg-brand-accent h-1.5 rounded-full transition-all duration-300" :style="'width: ' + freeShippingProgress + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Items List -->
                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    <template x-if="items.length === 0">
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-neutral-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <p class="text-neutral-500 text-sm mb-4">{{ __('store.cart_empty') }}</p>
                            <a href="{{ route('shop.index') }}" @click="open = false" class="inline-block bg-brand-black text-white px-6 py-2.5 text-xs font-display uppercase tracking-wider font-bold rounded hover:bg-brand-dark transition">
                                {{ __('store.cart_empty_cta') }}
                            </a>
                        </div>
                    </template>

                    <template x-for="item in items" :key="item.id">
                        <div class="flex space-x-4 border-b border-neutral-100 pb-5">
                            <img :src="item.image_url" :alt="item.product_name" class="w-20 h-24 object-cover rounded bg-neutral-100 shrink-0">
                            <div class="flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-sm font-semibold text-brand-black" x-text="item.product_name"></h3>
                                        <span class="text-sm font-bold text-brand-black ml-2" x-text="item.formatted_subtotal"></span>
                                    </div>
                                    <p class="text-xs text-neutral-500 mt-0.5" x-text="item.variant_label"></p>
                                </div>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center border border-brand-border rounded bg-white">
                                        <button @click="updateQuantity(item.id, item.quantity - 1)" :disabled="loading" class="px-2.5 py-1 text-neutral-600 hover:text-brand-black transition disabled:opacity-50" aria-label="Decrease quantity">-</button>
                                        <span class="px-2 text-xs font-bold" x-text="item.quantity"></span>
                                        <button @click="updateQuantity(item.id, item.quantity + 1)" :disabled="loading || item.quantity >= item.max_stock" class="px-2.5 py-1 text-neutral-600 hover:text-brand-black transition disabled:opacity-50" aria-label="Increase quantity">+</button>
                                    </div>
                                    <button @click="removeItem(item.id)" :disabled="loading" class="text-xs text-neutral-400 hover:text-red-600 transition" aria-label="Remove item">
                                        {{ __('store.cart_remove_item') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Drawer Footer & Checkout -->
                <div x-show="items.length > 0" class="p-6 border-t border-brand-border bg-brand-card-bg space-y-4">
                    <!-- Promo Code Input -->
                    <div>
                        <div class="flex space-x-2">
                            <input type="text" x-model="promoCodeInput" placeholder="{{ __('store.cart_promo_placeholder') }}" class="flex-1 text-xs px-3 py-2 border border-brand-border rounded uppercase focus:ring-1 focus:ring-brand-black focus:border-brand-black">
                            <button @click="applyPromo()" :disabled="loading || !promoCodeInput" class="bg-neutral-200 text-brand-black px-4 py-2 text-xs font-bold uppercase tracking-wider rounded hover:bg-neutral-300 transition disabled:opacity-50">
                                {{ __('store.cart_promo_apply') }}
                            </button>
                        </div>
                        <template x-if="promoError">
                            <p class="text-red-600 text-xs mt-1" x-text="promoError"></p>
                        </template>
                        <template x-if="discountCode">
                            <div class="flex items-center justify-between text-xs text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded mt-2">
                                <span x-html="'{{ __('store.cart_code_applied', ['code' => '<strong>:code</strong>']) }}'.replace(':code', discountCode)"></span>
                                <button @click="removePromo()" class="text-neutral-500 hover:text-red-600 ml-2">×</button>
                            </div>
                        </template>
                    </div>

                    <!-- Totals Breakdown -->
                    <div class="space-y-1.5 text-xs text-neutral-600">
                        <div class="flex justify-between">
                            <span>{{ __('store.cart_subtotal') }}</span>
                            <span class="text-brand-black font-semibold" x-text="formattedSubtotal"></span>
                        </div>
                        <template x-if="discountCode">
                            <div class="flex justify-between text-brand-accent">
                                <span>{{ __('store.cart_discount') }}</span>
                                <span class="font-bold" x-text="formattedDiscount"></span>
                            </div>
                        </template>
                        <div class="flex justify-between">
                            <span>{{ __('store.cart_shipping') }}</span>
                            <span class="text-brand-black font-semibold" x-text="formattedShipping"></span>
                        </div>
                        <div class="flex justify-between text-base font-display font-bold text-brand-black border-t border-brand-border pt-2 mt-2">
                            <span>{{ __('store.cart_total') }}</span>
                            <span x-text="formattedTotal"></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2 pt-2">
                        <a href="{{ route('checkout.index') }}" class="w-full block text-center bg-brand-black text-white py-3.5 rounded font-display uppercase tracking-widest text-xs font-bold hover:bg-brand-dark transition shadow-md">
                            {{ __('store.cart_checkout') }}
                        </a>
                        <a href="{{ route('cart.index') }}" @click="open = false" class="w-full block text-center text-xs text-neutral-500 hover:text-brand-black py-1 transition underline">
                            {{ __('store.cart_view') }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Flash Alerts / Toasts -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)" x-transition class="fixed bottom-6 right-6 z-50 bg-brand-black text-white px-5 py-3.5 rounded shadow-2xl flex items-center space-x-3 text-xs border border-brand-dark">
            <svg class="h-4 w-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="text-neutral-400 hover:text-white ml-2">×</button>
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition class="fixed bottom-6 right-6 z-50 bg-red-900 text-white px-5 py-3.5 rounded shadow-2xl flex items-center space-x-3 text-xs border border-red-800">
            <svg class="h-4 w-4 text-red-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="text-red-300 hover:text-white ml-2">×</button>
        </div>
    @endif

    <!-- Main Page Content -->
    <main id="main-content" class="flex-1 transition-opacity duration-150 ease-out">
        @yield('content')
    </main>

    <!-- Master Footer -->
    <footer class="bg-brand-black text-white border-t border-brand-dark mt-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
                <!-- Column 1: Brand Info -->
                <div class="lg:col-span-2 space-y-4">
                    <img src="{{ asset('images/logo.svg') }}" alt="RESET" class="h-7 w-auto invert">
                    <p class="text-neutral-400 text-xs leading-relaxed max-w-sm">
                        {{ __('store.footer_brand_desc') }}
                    </p>
                    <div class="pt-2">
                        <span class="text-[11px] font-display uppercase tracking-widest text-neutral-500 block mb-2">{{ __('store.footer_powered_by') }}</span>
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('images/payments/khqr-logo.svg') }}" alt="KHQR" class="h-7 w-auto">
                            <img src="{{ asset('images/payments/bakong-logo.svg') }}" alt="Bakong" class="h-7 w-auto">
                        </div>
                    </div>
                </div>

                <!-- Column 2: Shop Navigation -->
                <div>
                    <h3 class="text-xs font-display uppercase tracking-widest font-bold text-white mb-4">{{ __('store.footer_shop') }}</h3>
                    <ul class="space-y-2.5 text-xs text-neutral-400">
                        <li><a href="{{ route('shop.index') }}" class="hover:text-white transition">{{ __('store.catalog_title') }}</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'tees-and-tops']) }}" class="hover:text-white transition">{{ __('store.nav_tees') }}</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'hoodies-and-outerwear']) }}" class="hover:text-white transition">{{ __('store.nav_outerwear') }}</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'pants-and-bottoms']) }}" class="hover:text-white transition">{{ __('store.nav_bottoms') }}</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'accessories-and-headwear']) }}" class="hover:text-white transition">{{ __('store.nav_accessories') }}</a></li>
                    </ul>
                </div>

                <!-- Column 3: Help & Info -->
                <div>
                    <h3 class="text-xs font-display uppercase tracking-widest font-bold text-white mb-4">{{ __('store.footer_help') }}</h3>
                    <ul class="space-y-2.5 text-xs text-neutral-400">
                        <li><a href="{{ route('page.about') }}" class="hover:text-white transition">{{ __('store.footer_about') }}</a></li>
                        <li><a href="{{ route('page.faq') }}" class="hover:text-white transition">{{ __('store.footer_faq') }}</a></li>
                        <li><a href="{{ route('page.shipping') }}" class="hover:text-white transition">{{ __('store.footer_shipping') }}</a></li>
                        <li><a href="{{ route('page.size-guide') }}" class="hover:text-white transition">{{ __('store.footer_size_guide') }}</a></li>
                        <li><a href="{{ route('page.contact') }}" class="hover:text-white transition">{{ __('store.nav_contact') }}</a></li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter -->
                <div>
                    <h3 class="text-xs font-display uppercase tracking-widest font-bold text-white mb-4">{{ __('store.newsletter_title') }}</h3>
                    <p class="text-neutral-400 text-xs mb-3 leading-relaxed">
                        {{ __('store.newsletter_subtitle') }}
                    </p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="space-y-2">
                        @csrf
                        <input type="email" name="email" required placeholder="{{ __('store.newsletter_placeholder') }}" class="w-full px-3 py-2 text-xs bg-neutral-900 border border-neutral-800 rounded text-white placeholder-neutral-500 focus:outline-none focus:border-white">
                        <button type="submit" class="w-full bg-white text-brand-black font-display uppercase tracking-wider text-xs font-bold py-2 rounded hover:bg-neutral-200 transition">
                            {{ __('store.newsletter_submit') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="mt-12 pt-8 border-t border-neutral-800 flex flex-col sm:flex-row items-center justify-between text-xs text-neutral-500 space-y-4 sm:space-y-0">
                <p>&copy; {{ date('Y') }} RESET Clothing. {{ __('store.footer_rights') }}</p>
                <div class="flex space-x-6">
                    <a href="{{ route('page.privacy') }}" class="hover:text-white transition">{{ __('store.footer_privacy') }}</a>
                    <a href="{{ route('page.terms') }}" class="hover:text-white transition">{{ __('store.footer_terms') }}</a>
                    <a href="{{ route('sitemap') }}" class="hover:text-white transition">{{ __('store.sitemap') }}</a>
                    @if(auth()->user()?->isAdmin() || (auth()->guard('admin')->check() && auth()->guard('admin')->user()?->isAdmin()))
                        <a href="/admin" class="hover:text-white transition">{{ __('store.admin_panel') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <!-- Predictive Instant Search Modal -->
    @include('storefront.partials.search-modal')

    @stack('scripts')
</body>
</html>
