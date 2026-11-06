@extends('layouts.storefront', [
    'title' => __('store.cart_title') . ' — ' . __('store.brand_name'),
    'description' => 'Review your selected items and apply discounts.',
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-8 flex flex-wrap items-baseline justify-between gap-4">
        <div>
            <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.cart_title')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 mt-1 uppercase tracking-widest">
                {{ $cart->item_count }} {{ $cart->item_count === 1 ? __('store.item_singular') : __('store.items_count') }}
            </p>
        </div>
        <a href="{{ route('shop.index') }}" class="text-xs uppercase font-semibold tracking-widest text-brand-black/70 hover:text-brand-black transition underline underline-offset-4">
            ← @lang('store.cart_empty_cta')
        </a>
    </div>

    @if($cart->items->isEmpty())
        <div class="py-20 text-center max-w-md mx-auto">
            <div class="w-16 h-16 mx-auto mb-6 flex items-center justify-center rounded-full bg-brand-surface border border-black/10 text-brand-black/40">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <h2 class="text-xl font-display font-semibold uppercase tracking-tight text-brand-black mb-2">
                @lang('store.cart_empty')
            </h2>
            <p class="text-sm text-brand-black/60 mb-8">
                @lang('store.cart_empty_subtext')
            </p>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center justify-center px-8 py-4 bg-brand-black text-white text-xs font-semibold uppercase tracking-widest hover:bg-brand-orange transition duration-200">
                @lang('store.cart_empty_cta')
            </a>
        </div>
    @else
        <!-- Free Shipping Progress Bar -->
        <div class="mb-8 p-4 bg-brand-surface border border-black/5 rounded">
            @if($totals['free_shipping_qualified'])
                <div class="flex items-center gap-2 text-xs font-semibold tracking-wider text-green-700 uppercase">
                    <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>@lang('store.cart_free_shipping_unlocked')</span>
                </div>
            @else
                <div class="text-xs uppercase tracking-wider text-brand-black/80 font-medium mb-2">
                    @lang('store.cart_free_shipping_hint', ['amount' => $totals['free_shipping_remaining_formatted']])
                </div>
                @php
                    $threshold = (int) \App\Models\Setting::get('free_shipping_threshold', 200000);
                    $progress = min(100, round(($totals['subtotal_cents'] / ($threshold > 0 ? $threshold : 1)) * 100));
                @endphp
                <div class="w-full bg-black/10 h-1.5 overflow-hidden">
                    <div class="bg-brand-orange h-1.5 transition-all duration-300" style="width: {{ $progress }}%;"></div>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <!-- Items List -->
            <div class="lg:col-span-8 space-y-6">
                @foreach($cart->items as $item)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 border border-black/10 bg-white hover:border-black/30 transition">
                        <!-- Thumbnail & Details -->
                        <div class="flex items-center gap-4 w-full sm:w-auto">
                            <a href="{{ route('product.show', $item->variant->product->slug) }}" class="w-20 h-24 sm:w-24 sm:h-28 bg-brand-surface shrink-0 overflow-hidden border border-black/5">
                                <img src="{{ $item->variant->product->primary_image_url }}" alt="{{ $item->variant->product->localized_name }}" class="w-full h-full object-cover object-center">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('product.show', $item->variant->product->slug) }}" class="text-sm sm:text-base font-display font-semibold uppercase tracking-tight text-brand-black hover:text-brand-orange transition line-clamp-1">
                                    {{ $item->variant->product->localized_name }}
                                </a>
                                <p class="text-xs text-brand-black/60 uppercase tracking-wider mt-1">
                                    {{ $item->variant->label }}
                                </p>
                                <p class="text-xs font-semibold text-brand-black mt-1">
                                    {{ $item->variant->formatted_price }}
                                </p>
                            </div>
                        </div>

                        <!-- Quantity Control & Line Total -->
                        <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-black/5">
                            <!-- Update Quantity Form -->
                            <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center border border-black/20">
                                @csrf
                                @method('PATCH')
                                <button type="submit" name="quantity" value="{{ max(0, $item->quantity - 1) }}" class="w-8 h-8 flex items-center justify-center text-sm font-semibold hover:bg-black/5 transition" title="Decrease">
                                    −
                                </button>
                                <input type="number" readonly value="{{ $item->quantity }}" class="w-10 h-8 text-center text-xs font-bold border-0 bg-transparent p-0 focus:ring-0">
                                <button type="submit" name="quantity" value="{{ min($item->variant->available_stock, $item->quantity + 1) }}" {{ $item->quantity >= $item->variant->available_stock ? 'disabled' : '' }} class="w-8 h-8 flex items-center justify-center text-sm font-semibold hover:bg-black/5 disabled:opacity-30 transition" title="Increase">
                                    +
                                </button>
                            </form>

                            <!-- Subtotal -->
                            <div class="text-right min-w-[5rem]">
                                <span class="text-sm font-bold text-brand-black tracking-tight">
                                    {{ $item->formatted_subtotal }}
                                </span>
                            </div>

                            <!-- Remove Form -->
                            <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-brand-black/40 hover:text-red-600 transition p-1" title="@lang('store.cart_remove_item')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary Sidebar -->
            <div class="lg:col-span-4">
                <div class="p-6 bg-brand-surface border border-black/10 space-y-6 sticky top-24">
                    <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black pb-3 border-b border-black/10">
                        @lang('store.checkout_order_summary')
                    </h2>

                    <!-- Promo Code Form -->
                    <div>
                        @if($cart->discount)
                            <div class="flex items-center justify-between p-3 bg-white border border-black/10 text-xs">
                                <div>
                                    <span class="font-bold uppercase tracking-wider text-brand-black">{{ $cart->discount->code }}</span>
                                    <span class="text-brand-black/60 block">@lang('store.discount_off', ['value' => $cart->discount->formatted_value])</span>
                                </div>
                                <form action="{{ route('cart.discount.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs uppercase font-bold text-red-600 hover:underline">
                                        @lang('store.cart_promo_remove')
                                    </button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('cart.discount.apply') }}" method="POST" class="flex">
                                @csrf
                                <input type="text" name="code" required placeholder="@lang('store.cart_promo_placeholder')" class="flex-1 text-xs border border-black/20 px-3 py-2 uppercase tracking-wider focus:outline-none focus:border-brand-black">
                                <button type="submit" class="px-4 py-2 bg-brand-black text-white text-xs font-semibold uppercase tracking-widest hover:bg-brand-orange transition">
                                    @lang('store.cart_promo_apply')
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Totals Breakdown -->
                    <div class="space-y-3 text-xs uppercase tracking-wider border-t border-black/10 pt-4">
                        <div class="flex justify-between text-brand-black/70">
                            <span>@lang('store.cart_subtotal')</span>
                            <span class="font-semibold text-brand-black">{{ $totals['formatted_subtotal'] }}</span>
                        </div>

                        @if($totals['discount_cents'] > 0)
                            <div class="flex justify-between text-brand-orange font-semibold">
                                <span>@lang('store.cart_discount')</span>
                                <span>{{ $totals['formatted_discount'] }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-brand-black/70">
                            <span>@lang('store.cart_shipping')</span>
                            <span class="font-semibold text-brand-black">{{ $totals['formatted_shipping'] }}</span>
                        </div>

                        <div class="border-t border-black/10 pt-3 flex justify-between items-baseline text-sm font-bold text-brand-black">
                            <span>@lang('store.cart_total')</span>
                            <span class="text-lg font-display">{{ $totals['formatted_total'] }}</span>
                        </div>
                    </div>

                    <!-- Checkout CTA -->
                    <a href="{{ route('checkout.index') }}" class="w-full flex items-center justify-center py-4 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition duration-200 shadow-sm">
                        @lang('store.cart_checkout') →
                    </a>

                    <div class="text-center pt-2">
                        <p class="text-[10px] text-brand-black/50 uppercase tracking-widest">
                            @lang('store.cart_security_badge')
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
