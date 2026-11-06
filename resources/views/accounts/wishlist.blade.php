@extends('layouts.storefront', [
    'title' => __('store.account_wishlist') . ' — ' . __('store.brand_name'),
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-8">
        <h1 class="text-3xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.account_wishlist')
        </h1>
        <p class="text-xs text-brand-black/60 uppercase tracking-widest mt-1">@lang('store.account_wishlist_desc')</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">
        @include('accounts.partials.nav')

        <div class="flex-1">
            @if($products->isEmpty())
                <div class="bg-white border border-black/10 p-12 text-center">
                    <svg class="w-12 h-12 text-brand-black/30 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <p class="text-sm text-brand-black/60 mb-6">@lang('store.account_no_wishlist')</p>
                    <a href="{{ route('shop.index') }}" class="inline-block px-6 py-3 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                        @lang('store.cart_empty_cta')
                    </a>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                    @foreach($products as $product)
                        <div class="group border border-black/10 bg-white p-3 flex flex-col justify-between hover:border-black/30 transition">
                            <div>
                                <a href="{{ route('product.show', $product->slug) }}" class="block aspect-3/4 bg-brand-surface overflow-hidden relative mb-3">
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->localized_name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                </a>
                                <h3 class="text-xs font-bold uppercase tracking-tight text-brand-black truncate">
                                    <a href="{{ route('product.show', $product->slug) }}" class="hover:text-brand-orange transition">
                                        {{ $product->localized_name }}
                                    </a>
                                </h3>
                                <p class="text-xs font-semibold text-brand-black mt-1">
                                    {{ $product->formatted_min_price }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-black/10 flex items-center justify-between">
                                <a href="{{ route('product.show', $product->slug) }}" class="text-[11px] font-bold uppercase tracking-wider text-brand-black hover:text-brand-orange">
                                    @lang('store.view_product') →
                                </a>
                                <form action="{{ route('wishlist.toggle') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="text-brand-black/40 hover:text-red-600 transition" title="{{ __('store.wishlist_remove') }}">
                                        <svg class="w-4 h-4 fill-red-500 text-red-500" viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
