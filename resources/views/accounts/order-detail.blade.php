@extends('layouts.storefront', [
    'title' => __('store.payment_order_number') . ' #' . $order->number . ' — ' . __('store.brand_name'),
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-8 flex flex-wrap items-baseline justify-between gap-4">
        <div>
            <h1 class="text-3xl font-display font-bold uppercase tracking-tight text-brand-black">
                @lang('store.payment_order_number') #{{ $order->number }}
            </h1>
            <p class="text-xs text-brand-black/60 uppercase tracking-widest mt-1">
                @lang('store.order_placed_on', ['date' => $order->created_at->format('F d, Y \a\t h:i A')])
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('order.invoice', $order->number) }}" target="_blank" class="px-4 py-2 border border-black/20 text-xs font-bold uppercase tracking-wider text-brand-black hover:bg-black/5 transition">
                @lang('store.invoice_download')
            </a>
            <a href="{{ route('account.orders') }}" class="text-xs font-bold uppercase tracking-wider text-brand-black/70 hover:text-brand-black">
                @lang('store.back_to_orders')
            </a>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">
        @include('accounts.partials.nav')

        <div class="flex-1 space-y-8">
            <!-- Status Alert / Tracking -->
            <div class="bg-white border border-black/10 p-6">
                <div class="flex items-center justify-between flex-wrap gap-4 border-b border-black/10 pb-4 mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-black/60">@lang('store.fulfillment_status')</span>
                    <span class="px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-sm
                        {{ $order->status === \App\Enums\OrderStatus::Paid ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->status === \App\Enums\OrderStatus::PendingPayment ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $order->status === \App\Enums\OrderStatus::Shipped ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $order->status === \App\Enums\OrderStatus::Delivered ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $order->status === \App\Enums\OrderStatus::Cancelled || $order->status === \App\Enums\OrderStatus::Expired ? 'bg-gray-100 text-gray-800' : '' }}
                    ">
                        {{ $order->status->label() }}
                    </span>
                </div>

                @if($order->tracking_number)
                    <div class="p-4 bg-brand-surface border border-black/10 rounded text-xs space-y-1">
                        <p class="font-bold uppercase tracking-wider text-brand-black">@lang('store.shipping_and_tracking_info')</p>
                        <p class="text-brand-black/80">@lang('store.carrier'): <span class="font-semibold">{{ $order->shipping_carrier ?? 'Local Courier' }}</span></p>
                        <p class="text-brand-black/80">@lang('store.order_tracking_number'): <span class="font-mono font-bold">{{ $order->tracking_number }}</span></p>
                    </div>
                @endif

                @if($order->status === \App\Enums\OrderStatus::PendingPayment)
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded text-xs flex items-center justify-between">
                        <span class="text-amber-800">@lang('store.awaiting_payment_notice')</span>
                        <a href="{{ route('payment.show', $order->number) }}" class="px-4 py-2 bg-brand-black text-white font-bold uppercase tracking-wider hover:bg-brand-orange transition">
                            @lang('store.account_pay_now')
                        </a>
                    </div>
                @endif
            </div>

            <!-- Items -->
            <div class="bg-white border border-black/10 p-6">
                <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-6">
                    @lang('store.order_ordered_items')
                </h2>

                <div class="divide-y divide-black/5">
                    @foreach($order->items as $item)
                        <div class="py-4 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                            <div class="flex items-center gap-4">
                                @if($item->variant?->product)
                                    <a href="{{ route('product.show', $item->variant->product->slug) }}" class="w-16 h-20 bg-brand-surface border border-black/5 shrink-0 overflow-hidden">
                                        <img src="{{ $item->variant->product->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                    </a>
                                @endif
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold uppercase text-brand-black">{{ $item->product_name }}</h3>
                                    <p class="text-[11px] uppercase tracking-wider text-brand-black/60 mt-0.5">{{ $item->variant_label }}</p>
                                    <p class="text-xs text-brand-black/70 mt-1">{{ $item->formatted_price }} × {{ $item->quantity }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-brand-black font-mono shrink-0">
                                {{ $item->formatted_subtotal }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Totals -->
                <div class="mt-6 pt-6 border-t border-black/10 space-y-2 text-xs uppercase tracking-wider">
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
                        <span>@lang('store.shipping_fee')</span>
                        <span class="font-semibold text-brand-black">{{ $order->formatted_shipping }}</span>
                    </div>
                    <div class="pt-3 border-t border-black/10 flex justify-between items-baseline font-bold text-sm text-brand-black">
                        <span>@lang('store.order_total_paid')</span>
                        <span class="text-base font-display">{{ $order->formatted_total }}</span>
                    </div>
                </div>
            </div>

            <!-- Shipping & Delivery Address -->
            <div class="bg-white border border-black/10 p-6 text-xs">
                <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-4">
                    @lang('store.checkout_shipping_address')
                </h2>
                <div class="space-y-1 text-brand-black">
                    <p class="font-bold">{{ $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) }}</p>
                    <p>{{ $order->shipping_address['street_address'] ?? '' }}</p>
                    <p>{{ $order->shipping_address['commune'] ?? '' }} {{ $order->shipping_address['district'] ?? '' }}</p>
                    <p>{{ $order->shipping_address['province'] ?? '' }}, Cambodia</p>
                    <p class="text-brand-black/60 pt-1">@lang('store.phone'): {{ $order->shipping_address['phone'] ?? $order->phone }}</p>
                    @if(!empty($order->customer_notes))
                        <p class="text-brand-black/60 italic pt-2">@lang('store.order_notes'): "{{ $order->customer_notes }}"</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
