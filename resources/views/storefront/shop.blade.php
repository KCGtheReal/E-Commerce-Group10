@extends('layouts.storefront')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ mobileFiltersOpen: false }">
    @php
        $currentCategory = request('category') ? $categories->firstWhere('slug', request('category')) : null;
        $currencySymbol = \App\Services\CurrencyService::current() === 'KHR' ? '៛' : '$';
    @endphp

    <!-- Breadcrumb & Header -->
    <div class="mb-8 border-b border-brand-border dark:border-neutral-800 pb-6">
        <nav class="text-xs text-neutral-400 mb-2 font-display uppercase tracking-widest flex items-center space-x-2">
            <a href="{{ route('home') }}" class="hover:text-brand-black dark:hover:text-white transition">{{ __('store.nav_home') }}</a>
            <span>/</span>
            <a href="{{ route('shop.index') }}" class="hover:text-brand-black dark:hover:text-white transition {{ !request('category') && !request('q') ? 'text-brand-black dark:text-white font-semibold' : '' }}">{{ __('store.nav_shop') }}</a>
            @if(request('q'))
                <span>/</span>
                <span class="text-brand-black dark:text-white font-semibold uppercase">Search: "{{ request('q') }}"</span>
            @elseif($currentCategory)
                <span>/</span>
                <span class="text-brand-black dark:text-white font-semibold uppercase">{{ $currentCategory->localized_name }}</span>
            @endif
        </nav>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-display font-extrabold uppercase tracking-tight text-brand-black dark:text-white">
                    @if(request('q'))
                        Search: "{{ request('q') }}"
                    @elseif($currentCategory)
                        {{ $currentCategory->localized_name }}
                    @else
                        {{ __('store.catalog_title') }}
                    @endif
                </h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
                    @if(request('q'))
                        Showing all products matching your search term.
                    @elseif($currentCategory)
                        {{ $currentCategory->localized_description ?: __('store.catalog_subtitle') }}
                    @else
                        {{ __('store.catalog_subtitle') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs text-neutral-500 dark:text-neutral-400">
                    <span class="font-bold text-brand-black dark:text-white">{{ $products->total() }}</span> {{ __('store.products_found') }}
                </span>

                <!-- Mobile Filter Trigger Button -->
                <button
                    type="button"
                    @click="mobileFiltersOpen = true"
                    class="lg:hidden inline-flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-neutral-800 border border-brand-border dark:border-neutral-700 rounded-lg text-xs font-display font-bold uppercase tracking-wider text-brand-black dark:text-white shadow-xs hover:bg-neutral-50 dark:hover:bg-neutral-700 transition"
                >
                    <svg class="w-4 h-4 text-brand-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filters</span>
                    @if($activeFilterCount > 0)
                        <span class="w-5 h-5 rounded-full bg-brand-accent text-white text-[10px] font-bold flex items-center justify-center">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        <!-- Active Filters Bar -->
        @if(request()->hasAny(['category', 'size', 'color', 'min_price', 'max_price', 'in_stock', 'q', 'sort']))
            <div class="mt-4 pt-4 border-t border-brand-border/60 dark:border-neutral-800/80 flex items-center gap-2 flex-wrap">
                <span class="text-[11px] uppercase tracking-wider text-neutral-400 font-display font-semibold">Active filter:</span>

                @if(request('q'))
                    <a href="{{ route('shop.index', request()->except('q', 'page')) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-display font-medium bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 rounded-md hover:border-red-400 hover:text-red-600 transition group" title="Remove search">
                        <span>Query: "{{ request('q') }}"</span>
                        <span class="text-neutral-400 group-hover:text-red-600">&times;</span>
                    </a>
                @endif

                @if($currentCategory)
                    <a href="{{ route('shop.index', request()->except('category', 'page')) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-display font-medium bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 rounded-md hover:border-red-400 hover:text-red-600 transition group" title="Remove category">
                        <span>{{ $currentCategory->localized_name }}</span>
                        <span class="text-neutral-400 group-hover:text-red-600">&times;</span>
                    </a>
                @endif

                @foreach((array) request('size', []) as $sz)
                    @php
                        $remainingSizes = array_diff((array) request('size', []), [$sz]);
                    @endphp
                    <a href="{{ route('shop.index', array_merge(request()->except('size', 'page'), !empty($remainingSizes) ? ['size' => $remainingSizes] : [])) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-display font-medium bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 rounded-md hover:border-red-400 hover:text-red-600 transition group" title="Remove size {{ $sz }}">
                        <span>Size: {{ $sz }}</span>
                        <span class="text-neutral-400 group-hover:text-red-600">&times;</span>
                    </a>
                @endforeach

                @if(request('color'))
                    @php
                        $activeColorObj = $availableColors->firstWhere('color', request('color'));
                    @endphp
                    <a href="{{ route('shop.index', request()->except('color', 'page')) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-display font-medium bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 rounded-md hover:border-red-400 hover:text-red-600 transition group" title="Remove color filter">
                        @if($activeColorObj?->color_code)
                            <span class="w-2.5 h-2.5 rounded-full border border-black/20" style="background-color: {{ $activeColorObj->color_code }}"></span>
                        @endif
                        <span>Color: {{ request('color') }}</span>
                        <span class="text-neutral-400 group-hover:text-red-600">&times;</span>
                    </a>
                @endif

                @if(request('min_price') || request('max_price'))
                    <a href="{{ route('shop.index', request()->except('min_price', 'max_price', 'page')) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-display font-medium bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 rounded-md hover:border-red-400 hover:text-red-600 transition group" title="Remove price filter">
                        <span>Price: {{ request('min_price') ? number_format((float) request('min_price')) . ' ' . $currencySymbol : '0 ' . $currencySymbol }} - {{ request('max_price') ? number_format((float) request('max_price')) . ' ' . $currencySymbol : 'Any' }}</span>
                        <span class="text-neutral-400 group-hover:text-red-600">&times;</span>
                    </a>
                @endif

                @if(request('in_stock'))
                    <a href="{{ route('shop.index', request()->except('in_stock', 'page')) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-display font-medium bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 rounded-md hover:border-red-400 hover:text-red-600 transition group" title="Remove in-stock filter">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>In Stock Only</span>
                        <span class="text-neutral-400 group-hover:text-red-600">&times;</span>
                    </a>
                @endif

                <a href="{{ route('shop.index') }}" class="text-xs font-display font-bold uppercase tracking-wider text-brand-accent hover:text-red-600 transition ml-1 underline underline-offset-4">
                    {{ __('store.filter_clear') }}
                </a>
            </div>
        @endif
    </div>

    <!-- Main Layout: Sticky Filters Sidebar + Products Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        
        <!-- Desktop Sticky Sidebar Filters -->
        <aside class="hidden lg:block lg:sticky lg:top-24 self-start bg-white dark:bg-[#141414] border border-brand-border dark:border-neutral-800 rounded-2xl px-5 pt-5 shadow-xs max-h-[calc(100vh-7.5rem)] overflow-y-auto custom-scrollbar">
            
            <form action="{{ route('shop.index') }}" method="GET" id="filterForm" class="space-y-6">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif

                <!-- Sidebar Header with Active Badge & Clear -->
                <div class="flex items-center justify-between border-b border-brand-border dark:border-neutral-800 pb-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-brand-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <h2 class="text-xs font-display uppercase tracking-widest font-extrabold text-brand-black dark:text-white">
                            Filters
                        </h2>
                    </div>
                    @if($activeFilterCount > 0)
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded-full bg-brand-accent/10 text-brand-accent font-mono text-[10px] font-bold">
                                {{ $activeFilterCount }}
                            </span>
                            <a href="{{ route('shop.index', request()->only('q')) }}" class="text-[11px] text-neutral-400 hover:text-red-500 transition font-medium">
                                Reset
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Sorting Section -->
                <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                    <label for="sort" class="block text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2">
                        {{ __('store.sort_by') }}
                    </label>
                    <div class="relative">
                        <select name="sort" id="sort" onchange="this.form.submit()" class="w-full text-xs font-medium bg-neutral-50 dark:bg-neutral-800/80 border border-brand-border dark:border-neutral-700 rounded-lg py-2.5 px-3 pr-8 text-brand-black dark:text-white focus:outline-none focus:ring-1 focus:ring-brand-accent cursor-pointer transition">
                            <option value="featured" {{ request('sort') === 'featured' || !request('sort') ? 'selected' : '' }}>{{ __('store.sort_featured') }}</option>
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('store.sort_newest') }}</option>
                            <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>{{ __('store.sort_price_low') }}</option>
                            <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>{{ __('store.sort_price_high') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Categories Section with Counts -->
                <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                    <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2.5">
                        {{ __('store.filter_category') }}
                    </h3>
                    <div class="space-y-1">
                        <label class="flex items-center justify-between px-2.5 py-2 rounded-lg cursor-pointer transition text-xs {{ !request('category') ? 'bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white font-bold' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 hover:text-brand-black dark:hover:text-white' }}">
                            <div class="flex items-center space-x-2.5 truncate">
                                <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} onchange="this.form.submit()" class="sr-only">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ !request('category') ? 'bg-brand-accent' : 'bg-transparent' }}"></span>
                                <span class="truncate">{{ __('store.filter_all') }}</span>
                            </div>
                            <span class="text-[11px] font-mono text-neutral-400 dark:text-neutral-500 pl-2 shrink-0">{{ $allPublishedProductsCount }}</span>
                        </label>
                        @foreach($categories as $cat)
                            @php
                                $isCatActive = request('category') === $cat->slug;
                            @endphp
                            <label class="flex items-center justify-between px-2.5 py-2 rounded-lg cursor-pointer transition text-xs {{ $isCatActive ? 'bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white font-bold' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 hover:text-brand-black dark:hover:text-white' }}">
                                <div class="flex items-center space-x-2.5 truncate">
                                    <input type="radio" name="category" value="{{ $cat->slug }}" {{ $isCatActive ? 'checked' : '' }} onchange="this.form.submit()" class="sr-only">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $isCatActive ? 'bg-brand-accent' : 'bg-transparent' }}"></span>
                                    <span class="truncate">{{ $cat->localized_name }}</span>
                                </div>
                                <span class="text-[11px] font-mono text-neutral-400 dark:text-neutral-500 pl-2 shrink-0">{{ $cat->products_count }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Sizes Section (Canonical Order & Architectural Tiles) -->
                <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400">
                            {{ __('store.filter_size') }}
                        </h3>
                        @if(request('size'))
                            <a href="{{ route('shop.index', request()->except('size', 'page')) }}" class="text-[10px] text-neutral-400 hover:text-red-500 font-mono transition">Clear</a>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableSizes as $size)
                            @php
                                $isSizeActive = in_array($size, (array) request('size', []));
                            @endphp
                            <label class="cursor-pointer select-none">
                                <input type="checkbox" name="size[]" value="{{ $size }}" {{ $isSizeActive ? 'checked' : '' }} onchange="this.form.submit()" class="sr-only peer">
                                <span class="min-w-[40px] h-9 px-2.5 inline-flex items-center justify-center border border-brand-border dark:border-neutral-700 text-xs rounded-lg uppercase font-display font-semibold transition-all shadow-2xs peer-checked:bg-brand-black dark:peer-checked:bg-white peer-checked:text-white dark:peer-checked:text-black peer-checked:border-brand-black dark:peer-checked:border-white hover:border-brand-black dark:hover:border-neutral-400">
                                    {{ $size }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Compact Color Swatch Grid (Replaces 26-item vertical stack!) -->
                <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400">
                            {{ __('store.filter_color') }}
                        </h3>
                        @if(request('color'))
                            <a href="{{ route('shop.index', request()->except('color', 'page')) }}" class="text-[10px] text-neutral-400 hover:text-red-500 font-mono transition">Clear</a>
                        @endif
                    </div>

                    @if(request('color'))
                        <div class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900/50 text-xs mb-3">
                            <div class="flex items-center space-x-2 truncate">
                                @php
                                    $curColorObj = $availableColors->firstWhere('color', request('color'));
                                @endphp
                                <span class="w-3 h-3 rounded-full border border-black/20 shrink-0" style="background-color: {{ $curColorObj?->color_code ?? '#888' }}"></span>
                                <span class="font-medium text-brand-black dark:text-white truncate">{{ request('color') }}</span>
                            </div>
                            <a href="{{ route('shop.index', request()->except('color', 'page')) }}" class="text-neutral-400 hover:text-red-500 font-bold ml-2 leading-none" title="Clear color filter">&times;</a>
                        </div>
                    @endif

                    <div class="grid grid-cols-6 gap-2 pt-0.5 pb-2">
                        @foreach($availableColors as $col)
                            @php
                                $isColorSelected = request('color') === $col->color;
                            @endphp
                            <label class="relative flex items-center justify-center cursor-pointer group" title="{{ $col->color }}">
                                <input type="radio" name="color" value="{{ $col->color }}" {{ $isColorSelected ? 'checked' : '' }} onchange="this.form.submit()" class="sr-only">
                                <span
                                    class="w-7 h-7 rounded-full border border-black/15 dark:border-white/20 transition-all duration-200 flex items-center justify-center shadow-2xs group-hover:scale-110 {{ $isColorSelected ? 'ring-2 ring-brand-accent ring-offset-2 ring-offset-white dark:ring-offset-[#141414] scale-105' : '' }}"
                                    style="background-color: {{ $col->color_code }}"
                                >
                                    @if($isColorSelected)
                                        <svg class="w-3.5 h-3.5 text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.9)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Price Range Section with Presets -->
                <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400">
                            {{ __('store.filter_price') }} ({{ \App\Services\CurrencyService::current() }})
                        </h3>
                        @if(request('min_price') || request('max_price'))
                            <a href="{{ route('shop.index', request()->except('min_price', 'max_price', 'page')) }}" class="text-[10px] text-neutral-400 hover:text-red-500 font-mono transition">Clear</a>
                        @endif
                    </div>

                    <!-- Quick Price Presets -->
                    <div class="grid grid-cols-3 gap-1.5 mb-3">
                        @if(\App\Services\CurrencyService::current() === 'KHR')
                            <button type="button" onclick="document.getElementById('min_price').value=''; document.getElementById('max_price').value='100000'; document.getElementById('filterForm').submit();" class="py-1 px-1.5 text-[10px] font-mono font-medium uppercase text-center border border-brand-border dark:border-neutral-700 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition text-neutral-700 dark:text-neutral-300 cursor-pointer">&lt; 100K</button>
                            <button type="button" onclick="document.getElementById('min_price').value='100000'; document.getElementById('max_price').value='200000'; document.getElementById('filterForm').submit();" class="py-1 px-1.5 text-[10px] font-mono font-medium uppercase text-center border border-brand-border dark:border-neutral-700 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition text-neutral-700 dark:text-neutral-300 cursor-pointer">100K-200K</button>
                            <button type="button" onclick="document.getElementById('min_price').value='200000'; document.getElementById('max_price').value=''; document.getElementById('filterForm').submit();" class="py-1 px-1.5 text-[10px] font-mono font-medium uppercase text-center border border-brand-border dark:border-neutral-700 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition text-neutral-700 dark:text-neutral-300 cursor-pointer">&gt; 200K</button>
                        @else
                            <button type="button" onclick="document.getElementById('min_price').value=''; document.getElementById('max_price').value='25'; document.getElementById('filterForm').submit();" class="py-1 px-1.5 text-[10px] font-mono font-medium uppercase text-center border border-brand-border dark:border-neutral-700 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition text-neutral-700 dark:text-neutral-300 cursor-pointer">&lt; $25</button>
                            <button type="button" onclick="document.getElementById('min_price').value='25'; document.getElementById('max_price').value='50'; document.getElementById('filterForm').submit();" class="py-1 px-1.5 text-[10px] font-mono font-medium uppercase text-center border border-brand-border dark:border-neutral-700 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition text-neutral-700 dark:text-neutral-300 cursor-pointer">$25-$50</button>
                            <button type="button" onclick="document.getElementById('min_price').value='50'; document.getElementById('max_price').value=''; document.getElementById('filterForm').submit();" class="py-1 px-1.5 text-[10px] font-mono font-medium uppercase text-center border border-brand-border dark:border-neutral-700 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-800 transition text-neutral-700 dark:text-neutral-300 cursor-pointer">&gt; $50</button>
                        @endif
                    </div>

                    <div class="flex items-center space-x-2 text-xs">
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-neutral-400 font-mono text-[11px]">{{ $currencySymbol }}</span>
                            <input
                                type="number"
                                name="min_price"
                                id="min_price"
                                value="{{ request('min_price') }}"
                                placeholder="Min"
                                class="w-full pl-6 pr-2 py-2 bg-neutral-50 dark:bg-neutral-800/80 border border-brand-border dark:border-neutral-700 rounded-lg text-xs text-brand-black dark:text-white focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            >
                        </div>
                        <span class="text-neutral-400 shrink-0 font-medium">-</span>
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-neutral-400 font-mono text-[11px]">{{ $currencySymbol }}</span>
                            <input
                                type="number"
                                name="max_price"
                                id="max_price"
                                value="{{ request('max_price') }}"
                                placeholder="Max"
                                class="w-full pl-6 pr-2 py-2 bg-neutral-50 dark:bg-neutral-800/80 border border-brand-border dark:border-neutral-700 rounded-lg text-xs text-brand-black dark:text-white focus:outline-none focus:ring-1 focus:ring-brand-accent"
                            >
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="mt-3 w-full bg-brand-black dark:bg-white text-white dark:text-brand-black text-xs font-display font-bold uppercase tracking-wider py-2 rounded-lg hover:bg-brand-accent dark:hover:bg-brand-accent dark:hover:text-white transition shadow-xs flex items-center justify-center space-x-1 cursor-pointer"
                    >
                        <span>{{ __('store.filter_apply') }}</span>
                        <span>&rarr;</span>
                    </button>
                </div>

                <!-- In Stock Filter Toggle -->
                <div class="pb-2">
                    <label class="flex items-start space-x-3 cursor-pointer group select-none">
                        <div class="pt-0.5">
                            <input
                                type="checkbox"
                                name="in_stock"
                                value="1"
                                {{ request('in_stock') ? 'checked' : '' }}
                                onchange="this.form.submit()"
                                class="rounded border-brand-border dark:border-neutral-700 text-brand-accent focus:ring-brand-accent w-4 h-4 cursor-pointer"
                            >
                        </div>
                        <div class="text-xs">
                            <div class="flex items-center space-x-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 {{ request('in_stock') ? 'animate-pulse' : '' }}"></span>
                                <span class="font-bold text-brand-black dark:text-white group-hover:text-brand-accent transition">{{ __('store.filter_in_stock') }}</span>
                            </div>
                            <p class="text-[11px] text-neutral-400 mt-0.5">Ready for instant dispatch in Phnom Penh</p>
                        </div>
                    </label>
                </div>

                <!-- Clear All Filters Link -->
                @if($activeFilterCount > 0)
                    <div class="pb-5">
                        <a
                            href="{{ route('shop.index', request()->only('q')) }}"
                            class="block w-full text-center py-2.5 text-xs font-display font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 hover:text-red-500 transition border border-dashed border-brand-border dark:border-neutral-700 rounded-lg"
                        >
                            {{ __('store.filter_clear') }}
                        </a>
                    </div>
                @endif
            </form>
        </aside>

        <!-- Mobile Filter Off-Canvas Drawer -->
        <div
            x-show="mobileFiltersOpen"
            x-cloak
            class="fixed inset-0 z-50 lg:hidden"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop -->
            <div
                x-show="mobileFiltersOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs"
                @click="mobileFiltersOpen = false"
            ></div>

            <!-- Drawer Container -->
            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div
                    x-show="mobileFiltersOpen"
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-sm bg-white dark:bg-[#141414] shadow-2xl flex flex-col"
                >
                    <!-- Drawer Header -->
                    <div class="px-5 py-4 border-b border-brand-border dark:border-neutral-800 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-brand-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <h2 class="text-sm font-display uppercase tracking-widest font-extrabold text-brand-black dark:text-white">
                                Filter & Sort
                            </h2>
                            @if($activeFilterCount > 0)
                                <span class="px-2 py-0.5 rounded-full bg-brand-accent/10 text-brand-accent font-mono text-[10px] font-bold">
                                    {{ $activeFilterCount }}
                                </span>
                            @endif
                        </div>
                        <button
                            type="button"
                            @click="mobileFiltersOpen = false"
                            class="p-2 -mr-2 text-neutral-400 hover:text-brand-black dark:hover:text-white transition"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Drawer Body (Scrollable) -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-6 custom-scrollbar">
                        <form action="{{ route('shop.index') }}" method="GET" id="mobileFilterForm" class="space-y-6">
                            @if(request('q'))
                                <input type="hidden" name="q" value="{{ request('q') }}">
                            @endif

                            <!-- Mobile Sorting -->
                            <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                                <label for="m_sort" class="block text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2">
                                    {{ __('store.sort_by') }}
                                </label>
                                <select name="sort" id="m_sort" class="w-full text-xs font-medium bg-neutral-50 dark:bg-neutral-800/80 border border-brand-border dark:border-neutral-700 rounded-lg py-2.5 px-3 text-brand-black dark:text-white">
                                    <option value="featured" {{ request('sort') === 'featured' || !request('sort') ? 'selected' : '' }}>{{ __('store.sort_featured') }}</option>
                                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ __('store.sort_newest') }}</option>
                                    <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>{{ __('store.sort_price_low') }}</option>
                                    <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>{{ __('store.sort_price_high') }}</option>
                                </select>
                            </div>

                            <!-- Mobile Categories -->
                            <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                                <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2.5">
                                    {{ __('store.filter_category') }}
                                </h3>
                                <div class="space-y-1">
                                    <label class="flex items-center justify-between px-2.5 py-2 rounded-lg cursor-pointer text-xs {{ !request('category') ? 'bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white font-bold' : 'text-neutral-600 dark:text-neutral-400' }}">
                                        <div class="flex items-center space-x-2.5">
                                            <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} class="sr-only">
                                            <span class="w-1.5 h-1.5 rounded-full {{ !request('category') ? 'bg-brand-accent' : 'bg-transparent' }}"></span>
                                            <span>{{ __('store.filter_all') }}</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-neutral-400">{{ $allPublishedProductsCount }}</span>
                                    </label>
                                    @foreach($categories as $cat)
                                        <label class="flex items-center justify-between px-2.5 py-2 rounded-lg cursor-pointer text-xs {{ request('category') === $cat->slug ? 'bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white font-bold' : 'text-neutral-600 dark:text-neutral-400' }}">
                                            <div class="flex items-center space-x-2.5">
                                                <input type="radio" name="category" value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'checked' : '' }} class="sr-only">
                                                <span class="w-1.5 h-1.5 rounded-full {{ request('category') === $cat->slug ? 'bg-brand-accent' : 'bg-transparent' }}"></span>
                                                <span>{{ $cat->localized_name }}</span>
                                            </div>
                                            <span class="text-[11px] font-mono text-neutral-400">{{ $cat->products_count }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Mobile Sizes -->
                            <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                                <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2.5">
                                    {{ __('store.filter_size') }}
                                </h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableSizes as $size)
                                        @php
                                            $isSizeActive = in_array($size, (array) request('size', []));
                                        @endphp
                                        <label class="cursor-pointer select-none">
                                            <input type="checkbox" name="size[]" value="{{ $size }}" {{ $isSizeActive ? 'checked' : '' }} class="sr-only peer">
                                            <span class="min-w-[38px] h-8 px-2 inline-flex items-center justify-center border border-brand-border dark:border-neutral-700 text-xs rounded-lg uppercase font-display font-semibold peer-checked:bg-brand-black dark:peer-checked:bg-white peer-checked:text-white dark:peer-checked:text-black">
                                                {{ $size }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Mobile Colors -->
                            <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                                <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2.5">
                                    {{ __('store.filter_color') }}
                                </h3>
                                <div class="grid grid-cols-6 gap-2 pt-0.5 pb-2">
                                    @foreach($availableColors as $col)
                                        @php
                                            $isColorSelected = request('color') === $col->color;
                                        @endphp
                                        <label class="relative flex items-center justify-center cursor-pointer" title="{{ $col->color }}">
                                            <input type="radio" name="color" value="{{ $col->color }}" {{ $isColorSelected ? 'checked' : '' }} class="sr-only">
                                            <span
                                                class="w-7 h-7 rounded-full border border-black/15 dark:border-white/20 flex items-center justify-center {{ $isColorSelected ? 'ring-2 ring-brand-accent ring-offset-2 dark:ring-offset-[#141414]' : '' }}"
                                                style="background-color: {{ $col->color_code }}"
                                            >
                                                @if($isColorSelected)
                                                    <svg class="w-3.5 h-3.5 text-white drop-shadow-[0_1px_2px_rgba(0,0,0,0.9)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Mobile Price -->
                            <div class="border-b border-brand-border dark:border-neutral-800 pb-5">
                                <h3 class="text-[11px] font-display uppercase tracking-wider font-bold text-neutral-500 dark:text-neutral-400 mb-2.5">
                                    {{ __('store.filter_price') }} ({{ \App\Services\CurrencyService::current() }})
                                </h3>
                                <div class="flex items-center space-x-2 text-xs">
                                    <input
                                        type="number"
                                        name="min_price"
                                        value="{{ request('min_price') }}"
                                        placeholder="Min"
                                        class="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800/80 border border-brand-border dark:border-neutral-700 rounded-lg text-xs"
                                    >
                                    <span class="text-neutral-400">-</span>
                                    <input
                                        type="number"
                                        name="max_price"
                                        value="{{ request('max_price') }}"
                                        placeholder="Max"
                                        class="w-full px-3 py-2 bg-neutral-50 dark:bg-neutral-800/80 border border-brand-border dark:border-neutral-700 rounded-lg text-xs"
                                    >
                                </div>
                            </div>

                            <!-- Mobile In Stock -->
                            <div class="pb-2">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="in_stock"
                                        value="1"
                                        {{ request('in_stock') ? 'checked' : '' }}
                                        class="rounded border-brand-border dark:border-neutral-700 text-brand-accent focus:ring-brand-accent w-4 h-4"
                                    >
                                    <span class="text-xs font-bold text-brand-black dark:text-white">{{ __('store.filter_in_stock') }}</span>
                                </label>
                            </div>

                            <!-- Drawer Buttons -->
                            <div class="pt-4 space-y-2">
                                <button
                                    type="submit"
                                    class="w-full bg-brand-black dark:bg-white text-white dark:text-brand-black text-xs font-display font-bold uppercase tracking-wider py-3 rounded-lg hover:bg-brand-accent dark:hover:bg-brand-accent dark:hover:text-white transition shadow-sm"
                                >
                                    {{ __('store.filter_apply') }}
                                </button>
                                <a
                                    href="{{ route('shop.index', request()->only('q')) }}"
                                    class="block w-full text-center py-2.5 text-xs font-display font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400 hover:text-red-500 transition border border-dashed border-brand-border dark:border-neutral-700 rounded-lg"
                                >
                                    {{ __('store.filter_clear') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Cards Grid Column -->
        <main class="lg:col-span-3">
            @if($products->isEmpty())
                <div class="text-center py-16 px-4 bg-white dark:bg-[#141414] border border-brand-border dark:border-neutral-800 rounded-2xl space-y-4 shadow-xs">
                    <div class="w-14 h-14 mx-auto rounded-full bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center text-neutral-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    @if(request('q'))
                        <div>
                            <h3 class="text-base font-display font-bold uppercase tracking-wider text-brand-black dark:text-white">No matches for "{{ request('q') }}"</h3>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 max-w-md mx-auto">
                                Check your spelling, try generic terms like "tee" or "hoodie", or clear active filters.
                            </p>
                        </div>
                    @else
                        <div>
                            <h3 class="text-sm font-semibold text-brand-black dark:text-white mb-1">{{ __('store.no_products_found') }}</h3>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('store.no_products_found_hint') }}</p>
                        </div>
                    @endif
                    <div class="pt-2 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('shop.index') }}" class="inline-block bg-brand-black dark:bg-white text-white dark:text-brand-black px-5 py-2.5 text-xs font-display uppercase tracking-wider font-bold rounded-lg hover:bg-brand-accent dark:hover:bg-brand-accent dark:hover:text-white transition">
                            {{ __('store.filter_clear') }}
                        </a>
                        <button @click="$dispatch('open-search')" type="button" class="inline-block bg-neutral-100 dark:bg-neutral-800 text-brand-black dark:text-white border border-brand-border dark:border-neutral-700 px-5 py-2.5 text-xs font-display uppercase tracking-wider font-bold rounded-lg hover:bg-neutral-200 dark:hover:bg-neutral-700 transition cursor-pointer">
                            Open Live Search
                        </button>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-5 sm:gap-6">
                    @foreach($products as $product)
                        <div class="group flex flex-col justify-between">
                            <a href="{{ route('product.show', $product->slug) }}" class="block aspect-[3/4] bg-neutral-100 dark:bg-neutral-800 overflow-hidden rounded-xl relative border border-brand-border dark:border-neutral-800 shadow-2xs">
                                <img
                                    src="{{ $product->primary_image_url }}"
                                    alt="{{ $product->localized_name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                >
                                @if($product->compare_at_price_cents)
                                    <span class="absolute top-3 left-3 bg-brand-accent text-white text-[10px] font-bold uppercase tracking-widest px-2.5 py-0.5 rounded shadow-xs">
                                        {{ __('store.badge_sale') }}
                                    </span>
                                @endif
                                @if(!$product->is_in_stock)
                                    <span class="absolute inset-0 bg-white/70 dark:bg-black/75 backdrop-blur-xs flex items-center justify-center text-xs font-display font-bold uppercase tracking-wider text-brand-black dark:text-white">
                                        {{ __('store.out_of_stock') }}
                                    </span>
                                @endif
                            </a>
                            <div class="pt-3.5 flex justify-between items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] uppercase font-display tracking-widest text-neutral-400 block truncate">{{ $product->category->localized_name }}</span>
                                    <h3 class="text-sm font-semibold text-brand-black dark:text-white group-hover:text-brand-accent transition mt-0.5 truncate">
                                        <a href="{{ route('product.show', $product->slug) }}">{{ $product->localized_name }}</a>
                                    </h3>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 line-clamp-1 mt-0.5">{{ $product->localized_tagline }}</p>
                                </div>
                                <div class="text-right pl-2 shrink-0">
                                    <span class="text-sm font-display font-bold text-brand-black dark:text-white block">{{ $product->formatted_price }}</span>
                                    @if($product->formatted_compare_at_price)
                                        <span class="text-xs text-neutral-400 line-through">{{ $product->formatted_compare_at_price }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12">
                    {{ $products->links() }}
                </div>
            @endif
        </main>
    </div>
</div>
@endsection
