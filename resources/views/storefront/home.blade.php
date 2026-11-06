@extends('layouts.storefront')

@section('content')
<!-- Hero Section -->
<section class="relative bg-brand-black text-white min-h-[85vh] flex items-center overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1509967419530-da38b4704bc6?auto=format&fit=crop&w=1800&q=85" alt="RESET Collection" class="w-full h-full object-cover object-center opacity-35 filter grayscale contrast-125">
        <div class="absolute inset-0 bg-gradient-to-t from-brand-black via-brand-black/40 to-transparent"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 sm:py-32">
        <div class="max-w-2xl space-y-6">
            <span class="inline-block bg-brand-accent text-white text-[11px] font-display uppercase tracking-widest px-3 py-1 font-bold rounded">
                {{ __('store.badge_capsule') }}
            </span>
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-display font-extrabold tracking-tighter leading-tight uppercase">
                {{ __('store.hero_title') }}
            </h1>
            <p class="text-neutral-300 text-base sm:text-lg leading-relaxed max-w-xl font-light">
                {{ __('store.hero_subtitle') }}
            </p>
            <div class="pt-4 flex flex-col sm:flex-row gap-4">
                <a href="{{ route('shop.index') }}" class="inline-flex items-center justify-center bg-white text-brand-black px-8 py-4 text-xs font-display font-bold uppercase tracking-widest hover:bg-neutral-200 transition shadow-lg">
                    {{ __('store.hero_cta_shop') }}
                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
                <a href="{{ route('page.about') }}" class="inline-flex items-center justify-center border border-white/40 text-white px-8 py-4 text-xs font-display font-bold uppercase tracking-widest hover:bg-white/10 transition backdrop-blur-sm">
                    {{ __('store.hero_cta_story') }}
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Trust Banner -->
<section class="bg-brand-bone border-y border-brand-border py-6 px-4">
    <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-6 text-center text-xs text-neutral-600 font-display uppercase tracking-wider">
        <div class="flex items-center justify-center space-x-3">
            <svg class="h-5 w-5 text-brand-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-bold text-brand-black">{{ __('store.trust_bakong_certified') }}</span>
        </div>
        <div class="flex items-center justify-center space-x-3">
            <svg class="h-5 w-5 text-brand-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
            </svg>
            <span class="font-bold text-brand-black">{{ __('store.free_delivery_feature', ['amount' => \App\Services\CurrencyService::formatCents((int) \App\Models\Setting::get('free_shipping_threshold', 5000))]) }}</span>
        </div>
        <div class="flex items-center justify-center space-x-3">
            <svg class="h-5 w-5 text-brand-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <span class="font-bold text-brand-black">{{ __('store.trust_organic_cotton') }}</span>
        </div>
    </div>
</section>

<!-- Featured Collections Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10">
        <div>
            <span class="text-xs font-display uppercase tracking-widest text-brand-accent font-bold">{{ __('store.section_categories') }}</span>
            <h2 class="text-3xl font-display font-extrabold tracking-tight uppercase text-brand-black mt-1">
                {{ __('store.featured_collections') }}
            </h2>
        </div>
        <a href="{{ route('shop.index') }}" class="text-xs font-display uppercase tracking-wider font-bold text-neutral-600 hover:text-brand-black transition mt-2 sm:mt-0 underline underline-offset-4">
            {{ __('store.view_all') }} &rarr;
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($categories as $category)
            <a href="{{ route('shop.index', ['category' => $category->slug]) }}" class="group relative block aspect-[4/5] bg-neutral-200 overflow-hidden rounded">
                <img src="{{ $category->image_url }}" alt="{{ $category->localized_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out filter grayscale contrast-110">
                <div class="absolute inset-0 bg-gradient-to-t from-brand-black/80 via-brand-black/20 to-transparent"></div>
                <div class="absolute bottom-0 inset-x-0 p-6">
                    <h3 class="text-xl font-display font-bold text-white uppercase tracking-wider">
                        {{ $category->localized_name }}
                    </h3>
                    <p class="text-xs text-neutral-300 mt-1 line-clamp-1 font-light">
                        {{ $category->localized_description }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>
</section>

<!-- New Arrivals Showcase -->
<section class="bg-brand-bone py-20 border-y border-brand-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-12">
            <div>
                <span class="text-xs font-display uppercase tracking-widest text-brand-accent font-bold">{{ __('store.section_just_dropped') }}</span>
                <h2 class="text-3xl font-display font-extrabold tracking-tight uppercase text-brand-black mt-1">
                    {{ __('store.new_arrivals') }}
                </h2>
                <p class="text-neutral-500 text-xs mt-1">{{ __('store.new_arrivals_desc') }}</p>
            </div>
            <a href="{{ route('shop.index', ['sort' => 'newest']) }}" class="text-xs font-display uppercase tracking-wider font-bold text-neutral-600 hover:text-brand-black transition mt-2 sm:mt-0 underline underline-offset-4">
                {{ __('store.view_all') }} &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($newArrivals as $product)
                <div class="group flex flex-col justify-between">
                    <a href="{{ route('product.show', $product->slug) }}" class="block aspect-[3/4] bg-white overflow-hidden rounded relative border border-brand-border">
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->localized_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @if($product->compare_at_price_cents)
                            <span class="absolute top-3 left-3 bg-brand-accent text-white text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded">
                                {{ __('store.badge_sale') }}
                            </span>
                        @endif
                        <span class="absolute bottom-3 right-3 bg-brand-black/80 text-white text-[10px] font-display uppercase px-2 py-1 rounded backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity">
                            {{ __('store.quick_view') }}
                        </span>
                    </a>
                    <div class="pt-4 flex justify-between items-start">
                        <div>
                            <span class="text-[10px] uppercase font-display tracking-widest text-neutral-400 block">{{ $product->category->localized_name }}</span>
                            <h3 class="text-sm font-semibold text-brand-black group-hover:text-brand-accent transition mt-0.5">
                                <a href="{{ route('product.show', $product->slug) }}">{{ $product->localized_name }}</a>
                            </h3>
                            <p class="text-xs text-neutral-500 line-clamp-1 mt-0.5">{{ $product->localized_tagline }}</p>
                        </div>
                        <div class="text-right pl-2 shrink-0">
                            <span class="text-sm font-display font-bold text-brand-black block">{{ $product->formatted_price }}</span>
                            @if($product->formatted_compare_at_price)
                                <span class="text-xs text-neutral-400 line-through">{{ $product->formatted_compare_at_price }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Brand Editorial Story Section -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div class="aspect-[4/5] bg-neutral-200 overflow-hidden rounded">
            <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=1200&q=80" alt="RESET Studio Phnom Penh" class="w-full h-full object-cover filter contrast-110">
        </div>
        <div class="space-y-6 lg:pl-8">
            <span class="text-xs font-display uppercase tracking-widest text-brand-accent font-bold">{{ __('store.section_philosophy') }}</span>
            <h2 class="text-3xl sm:text-5xl font-display font-black tracking-tight uppercase text-brand-black leading-tight">
                {{ __('store.brand_story_title') }}
            </h2>
            <p class="text-neutral-600 text-sm sm:text-base leading-relaxed">
                {{ __('store.brand_story_text') }}
            </p>
            <div class="border-l-2 border-brand-accent pl-4 py-1 italic text-sm text-neutral-700">
                {{ __('store.craft_quote') }}
            </div>
            <div class="pt-4">
                <a href="{{ route('page.about') }}" class="inline-block bg-brand-black text-white px-8 py-3.5 text-xs font-display font-bold uppercase tracking-widest hover:bg-brand-dark transition">
                    {{ __('store.learn_our_craft') }}
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Best Sellers Showcase -->
<section class="bg-brand-offwhite py-20 border-t border-brand-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-12">
            <div>
                <span class="text-xs font-display uppercase tracking-widest text-brand-accent font-bold">{{ __('store.section_essentials') }}</span>
                <h2 class="text-3xl font-display font-extrabold tracking-tight uppercase text-brand-black mt-1">
                    {{ __('store.best_sellers') }}
                </h2>
                <p class="text-neutral-500 text-xs mt-1">{{ __('store.best_sellers_desc') }}</p>
            </div>
            <a href="{{ route('shop.index') }}" class="text-xs font-display uppercase tracking-wider font-bold text-neutral-600 hover:text-brand-black transition mt-2 sm:mt-0 underline underline-offset-4">
                {{ __('store.view_all') }} &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($bestSellers as $product)
                <div class="group flex flex-col justify-between">
                    <a href="{{ route('product.show', $product->slug) }}" class="block aspect-[3/4] bg-white overflow-hidden rounded relative border border-brand-border">
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->localized_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <span class="absolute top-3 left-3 bg-brand-black text-white text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded">
                            {{ __('store.badge_bestseller') }}
                        </span>
                    </a>
                    <div class="pt-4 flex justify-between items-start">
                        <div>
                            <span class="text-[10px] uppercase font-display tracking-widest text-neutral-400 block">{{ $product->category->localized_name }}</span>
                            <h3 class="text-sm font-semibold text-brand-black group-hover:text-brand-accent transition mt-0.5">
                                <a href="{{ route('product.show', $product->slug) }}">{{ $product->localized_name }}</a>
                            </h3>
                            <p class="text-xs text-neutral-500 line-clamp-1 mt-0.5">{{ $product->localized_tagline }}</p>
                        </div>
                        <div class="text-right pl-2 shrink-0">
                            <span class="text-sm font-display font-bold text-brand-black block">{{ $product->formatted_price }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
