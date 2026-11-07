@extends('layouts.storefront', [
    'title' => __('store.success_title') . ' — ' . __('store.brand_name'),
    'description' => 'Your RESET order has been confirmed and is being processed.',
])

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="bg-white border border-black/10 p-6 sm:p-10 shadow-sm">
        <!-- Success Icon & Header -->
        <div class="text-center pb-8 border-b border-black/10">
            <div class="w-16 h-16 rounded-full bg-green-50 border border-green-200 text-green-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <span class="text-xs font-bold uppercase tracking-widest text-green-700">
                @lang('store.order_status_paid')
            </span>
            <h1 class="text-2xl sm:text-3xl font-display font-bold uppercase tracking-tight text-brand-black mt-1">
                @lang('store.success_title')
            </h1>
            <p class="text-xs sm:text-sm text-brand-black/60 mt-2 max-w-md mx-auto">
                @lang('store.success_subtitle')
            </p>
        </div>

        <!-- Order Meta Box -->
        <div class="py-6 border-b border-black/10 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-brand-black/50 uppercase tracking-wider block">@lang('store.success_order_number')</span>
                <span class="font-mono font-bold text-brand-black text-sm mt-0.5 block">#{{ $order->number }}</span>
            </div>
            <div>
                <span class="text-brand-black/50 uppercase tracking-wider block">@lang('store.order_date')</span>
                <span class="font-semibold text-brand-black mt-0.5 block">{{ $order->created_at->format('M d, Y') }}</span>
            </div>
            <div>
                <span class="text-brand-black/50 uppercase tracking-wider block">@lang('store.order_payment_method')</span>
                <span class="font-semibold text-brand-black mt-0.5 block">Bakong KHQR</span>
            </div>
            <div>
                <span class="text-brand-black/50 uppercase tracking-wider block">@lang('store.cart_total')</span>
                <span class="font-bold text-brand-black text-sm mt-0.5 block">{{ $order->formatted_total }}</span>
            </div>
        </div>

        <!-- Delivery Destination -->
        <div class="py-6 border-b border-black/10">
            <h2 class="text-xs font-bold uppercase tracking-wider text-brand-black/70 mb-2">
                @lang('store.checkout_shipping_address')
            </h2>
            <div class="text-xs text-brand-black space-y-1">
                <p class="font-bold uppercase">{{ $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) }}</p>
                <p>{{ $order->shipping_address['street_address'] ?? '' }}</p>
                <p>{{ $order->shipping_address['commune'] ?? '' }} {{ $order->shipping_address['district'] ?? '' }}</p>
                <p>{{ $order->shipping_address['province'] ?? '' }}, Cambodia</p>
                <p class="text-brand-black/70">@lang('store.checkout_phone'): {{ $order->shipping_address['phone'] ?? $order->phone }}</p>
            </div>
        </div>

        <!-- Itemized Order Items -->
        <div class="py-6 border-b border-black/10">
            <h2 class="text-xs font-bold uppercase tracking-wider text-brand-black/70 mb-4">
                @lang('store.order_ordered_items')
            </h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center justify-between gap-4 text-xs">
                        <div class="min-w-0">
                            <p class="font-bold uppercase tracking-tight text-brand-black">{{ $item->product_name }}</p>
                            <p class="text-brand-black/60 text-[11px] uppercase">{{ $item->variant_label }} × {{ $item->quantity }}</p>
                        </div>
                        <span class="font-bold shrink-0">{{ $item->formatted_subtotal }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Financial Summary -->
            <div class="mt-6 pt-4 border-t border-black/5 space-y-2 text-xs uppercase tracking-wider">
                <div class="flex justify-between text-brand-black/70">
                    <span>@lang('store.cart_subtotal')</span>
                    <span class="font-semibold text-brand-black">{{ $order->formatted_subtotal }}</span>
                </div>
                @if($order->discount_cents > 0)
                    <div class="flex justify-between text-brand-orange font-semibold">
                        <span>@lang('store.cart_discount') ({{ $order->discount_code }})</span>
                        <span>{{ $order->formatted_discount }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-brand-black/70">
                    <span>@lang('store.cart_shipping')</span>
                    <span class="font-semibold text-brand-black">
                        {{ $order->formatted_shipping }}
                    </span>
                </div>
                <div class="pt-2 border-t border-black/10 flex justify-between items-baseline font-bold text-sm text-brand-black">
                    <span>@lang('store.order_total_paid')</span>
                    <div class="text-right">
                        <span>{{ $order->formatted_total }}</span>
                        @php
                            $rate = (int) ($order->exchange_rate ?: 4100);
                        @endphp
                        @if($order->currency === 'KHR')
                            <div class="text-[11px] font-normal text-brand-black/60">
                                ≈ ${{ number_format($rate > 0 ? $order->total_cents / $rate : 0, 2) }} USD
                            </div>
                        @else
                            <div class="text-[11px] font-normal text-brand-black/60">
                                ≈ {{ number_format(round($order->total_cents * $rate), 0) }} ៛ KHR
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Links -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <a href="{{ route('order.invoice', $order->number) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 min-h-[50px] border border-black/20 dark:border-neutral-700 text-xs font-bold uppercase tracking-widest text-brand-black dark:text-neutral-100 hover:bg-black/5 dark:hover:bg-neutral-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                @lang('store.invoice_download')
            </a>

            <div class="grid grid-cols-2 gap-3 w-full sm:w-auto sm:flex sm:items-center">
                @auth
                    <a href="{{ route('account.orders') }}" class="flex-1 inline-flex items-center justify-center min-h-[50px] px-6 py-3 border border-black/20 dark:border-neutral-700 text-xs font-bold uppercase tracking-widest text-brand-black dark:text-neutral-100 hover:bg-black/5 dark:hover:bg-neutral-800 text-center transition">
                        @lang('store.nav_orders')
                    </a>
                @endauth
                <a href="{{ route('shop.index') }}" class="flex-1 inline-flex items-center justify-center min-h-[50px] px-6 py-3 bg-brand-black dark:bg-white text-white dark:text-brand-black text-xs font-bold uppercase tracking-widest hover:bg-brand-orange dark:hover:bg-neutral-200 text-center transition">
                    @lang('store.success_continue_shopping')
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
