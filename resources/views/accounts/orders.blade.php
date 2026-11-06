@extends('layouts.storefront', [
    'title' => __('store.account_orders') . ' — ' . __('store.brand_name'),
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-8">
        <h1 class="text-3xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.account_orders')
        </h1>
        <p class="text-xs text-brand-black/60 uppercase tracking-widest mt-1">@lang('store.account_orders_desc')</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">
        @include('accounts.partials.nav')

        <div class="flex-1">
            @if($orders->isEmpty())
                <div class="bg-white border border-black/10 p-12 text-center">
                    <p class="text-sm text-brand-black/60 mb-6">@lang('store.account_no_orders')</p>
                    <a href="{{ route('shop.index') }}" class="inline-block px-6 py-3 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                        @lang('store.cart_empty_cta')
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($orders as $order)
                        <div class="bg-white border border-black/10 p-6 hover:border-black/30 transition">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-black/10 pb-4 mb-4">
                                <div>
                                    <span class="text-xs text-brand-black/50 uppercase">@lang('store.payment_order_number')</span>
                                    <h3 class="font-mono font-bold text-sm text-brand-black">#{{ $order->number }}</h3>
                                    <p class="text-[11px] text-brand-black/60">{{ $order->created_at->format('M d, Y • h:i A') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="px-3 py-1 text-[11px] font-bold uppercase tracking-wider rounded-sm
                                        {{ $order->status === \App\Enums\OrderStatus::Paid ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $order->status === \App\Enums\OrderStatus::PendingPayment ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $order->status === \App\Enums\OrderStatus::Shipped ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $order->status === \App\Enums\OrderStatus::Delivered ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $order->status === \App\Enums\OrderStatus::Cancelled || $order->status === \App\Enums\OrderStatus::Expired ? 'bg-gray-100 text-gray-800' : '' }}
                                    ">
                                        {{ $order->status->label() }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div class="text-xs text-brand-black/80 space-y-1">
                                    <p class="font-semibold">{{ trans_choice('store.order_items_count', $order->items->count(), ['count' => $order->items->count()]) }}</p>
                                    <p class="text-brand-black/60">{{ $order->items->take(2)->pluck('product_name')->join(', ') }}{{ $order->items->count() > 2 ? __('store.and_more_items') : '' }}</p>
                                </div>

                                <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end pt-3 sm:pt-0 border-t sm:border-t-0 border-black/5">
                                    <div class="text-right">
                                        <span class="text-xs text-brand-black/50 uppercase block">@lang('store.cart_total')</span>
                                        <span class="text-base font-bold text-brand-black font-display">{{ $order->formatted_total }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if($order->status === \App\Enums\OrderStatus::PendingPayment)
                                            <a href="{{ route('payment.show', $order->number) }}" class="px-3 py-2 bg-brand-orange text-white text-xs font-bold uppercase tracking-wider hover:bg-black transition">
                                                @lang('store.account_pay_now')
                                            </a>
                                        @endif
                                        <a href="{{ route('account.orders.show', $order->number) }}" class="px-3 py-2 border border-black/20 text-xs font-bold uppercase tracking-wider text-brand-black hover:bg-black/5 transition">
                                            @lang('store.account_details')
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
