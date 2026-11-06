<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #{{ $order->number }} — {{ config('app.name', 'RESET') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; color: #000 !important; background: #fff !important; }
            .print-border { border-color: #000 !important; }
        }
        body { font-family: 'Space Grotesk', 'Kantumruy Pro', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4 text-gray-900 antialiased">
    <div class="max-w-3xl mx-auto bg-white border border-gray-200 p-8 sm:p-12 shadow-sm">
        <!-- Print Header Action -->
        <div class="no-print flex items-center justify-between pb-6 mb-6 border-b border-gray-200">
            <a href="{{ url()->previous() ?: route('home') }}" class="text-xs font-bold uppercase tracking-wider text-gray-500 hover:text-black">
                @lang('store.invoice_back')
            </a>
            <button onclick="window.print()" class="px-5 py-2 bg-black text-white text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition">
                @lang('store.invoice_print')
            </button>
        </div>

        <!-- Brand & Invoice Header -->
        <div class="flex items-start justify-between border-b-2 border-black pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-bold tracking-tight uppercase">RESET</h1>
                <p class="text-xs uppercase tracking-widest text-gray-500 mt-0.5">@lang('store.brand_tagline')</p>
                <div class="mt-4 text-xs text-gray-600 leading-relaxed">
                    <p class="font-bold text-gray-900">{{ $storeInfo['name'] ?? 'RESET Store' }}</p>
                    <p>{{ $storeInfo['address'] ?? 'Phnom Penh, Cambodia' }}</p>
                    <p>@lang('store.phone'): {{ $storeInfo['phone'] ?? '+855 23 999 123' }}</p>
                    <p>@lang('store.email'): {{ $storeInfo['email'] ?? 'support@reset.store' }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-black text-white text-xs font-bold uppercase tracking-widest mb-3">
                    @lang('store.invoice_tax_invoice')
                </span>
                <p class="text-xs text-gray-500 uppercase">@lang('store.invoice_number')</p>
                <p class="text-base font-mono font-bold">#{{ $order->number }}</p>
                <p class="text-xs text-gray-500 uppercase mt-2">@lang('store.invoice_date')</p>
                <p class="text-xs font-semibold">{{ $order->created_at->format('F d, Y') }}</p>
                <p class="text-xs text-gray-500 uppercase mt-2">@lang('store.invoice_payment_status')</p>
                <p class="text-xs font-bold text-green-700 uppercase">{{ $order->status->label() }}</p>
            </div>
        </div>

        <!-- Bill To / Ship To -->
        <div class="grid grid-cols-2 gap-8 border-b border-gray-200 pb-8 mb-8 text-xs">
            <div>
                <h2 class="font-bold uppercase tracking-wider text-gray-400 mb-2">@lang('store.invoice_customer_billed_to')</h2>
                <p class="font-bold text-sm text-gray-900">{{ $order->shipping_address['full_name'] ?? $order->shipping_address['name'] ?? trim(($order->shipping_address['first_name'] ?? '') . ' ' . ($order->shipping_address['last_name'] ?? '')) }}</p>
                <p class="text-gray-600">{{ $order->email }}</p>
                <p class="text-gray-600">{{ $order->shipping_address['phone'] ?? $order->phone }}</p>
            </div>
            <div>
                <h2 class="font-bold uppercase tracking-wider text-gray-400 mb-2">@lang('store.invoice_shipping_destination')</h2>
                <p class="text-gray-800">{{ $order->shipping_address['street_address'] ?? '' }}</p>
                <p class="text-gray-800">{{ $order->shipping_address['commune'] ?? '' }} {{ $order->shipping_address['district'] ?? '' }}</p>
                <p class="text-gray-800">{{ $order->shipping_address['province'] ?? '' }}, Cambodia</p>
                <p class="text-gray-500 mt-1">@lang('store.invoice_method'): {{ ucfirst($order->shipping_method ?? 'Standard') }}</p>
            </div>
        </div>

        <!-- Itemized Table -->
        <table class="w-full text-xs text-left mb-8">
            <thead>
                <tr class="border-b-2 border-black text-gray-600 uppercase tracking-wider">
                    <th class="py-2">@lang('store.invoice_item_desc')</th>
                    <th class="py-2 text-center">@lang('store.invoice_qty')</th>
                    <th class="py-2 text-right">@lang('store.invoice_unit_price')</th>
                    <th class="py-2 text-right">@lang('store.invoice_amount')</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <tr>
                        <td class="py-3 pr-4">
                            <p class="font-bold text-gray-900 uppercase">{{ $item->product_name }}</p>
                            <p class="text-gray-500 uppercase text-[10px]">{{ $item->variant_label }}</p>
                        </td>
                        <td class="py-3 text-center font-semibold">{{ $item->quantity }}</td>
                        <td class="py-3 text-right font-mono">{{ $item->formatted_price }}</td>
                        <td class="py-3 text-right font-mono font-bold">{{ $item->formatted_subtotal }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Calculation -->
        <div class="border-t-2 border-black pt-4 flex justify-end mb-8">
            <div class="w-64 space-y-2 text-xs">
                <div class="flex justify-between text-gray-600 uppercase">
                    <span>@lang('store.cart_subtotal')</span>
                    <span class="font-mono font-semibold">{{ $order->formatted_subtotal }}</span>
                </div>
                @if($order->discount_cents > 0)
                    <div class="flex justify-between text-red-600 uppercase">
                        <span>@lang('store.cart_discount') ({{ $order->discount_code }})</span>
                        <span class="font-mono font-semibold">{{ $order->formatted_discount }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-gray-600 uppercase">
                    <span>@lang('store.shipping_fee')</span>
                    <span class="font-mono font-semibold">{{ $order->formatted_shipping }}</span>
                </div>
                <div class="border-t border-gray-200 pt-2 flex justify-between items-baseline font-bold text-sm text-gray-900">
                    <span class="uppercase">@lang('store.total_amount')</span>
                    <div class="text-right font-mono">
                        <div>{{ $order->formatted_total }}</div>
                        @php
                            $rate = (int) ($order->exchange_rate ?: 4100);
                        @endphp
                        @if($order->currency === 'KHR')
                            <div class="text-xs font-normal text-gray-500">
                                ≈ ${{ number_format($rate > 0 ? $order->total_cents / $rate : 0, 2) }} USD
                            </div>
                        @else
                            <div class="text-xs font-normal text-gray-500">
                                ≈ {{ number_format(round($order->total_cents * $rate), 0) }} ៛ KHR
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Authentication Footnote -->
        <div class="border-t border-gray-200 pt-6 text-[11px] text-gray-500 leading-relaxed">
            <p class="font-bold text-gray-800 uppercase tracking-wider mb-1">@lang('store.invoice_settlement_title')</p>
            @if($order->latestPayment)
                <p>@lang('store.invoice_payment_id'): <span class="font-mono">{{ $order->latestPayment->id }}</span> • @lang('store.invoice_md5_hash'): <span class="font-mono">{{ $order->latestPayment->md5 }}</span></p>
                <p>@lang('store.invoice_settled_account'): <span class="font-mono">{{ $order->latestPayment->account_id }}</span> (@lang('store.invoice_certified_settlement'))</p>
            @endif
            <p class="mt-4 text-center text-gray-400 uppercase tracking-widest text-[10px]">@lang('store.invoice_thank_you')</p>
        </div>
    </div>
</body>
</html>
