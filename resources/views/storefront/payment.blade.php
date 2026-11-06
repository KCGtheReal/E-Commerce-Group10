@extends('layouts.storefront', [
    'title' => __('store.payment_title') . ' — ' . __('store.brand_name'),
    'description' => 'Scan Bakong KHQR to complete your RESET order payment.',
])

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-brand-bone/40">
    <div class="max-w-md w-full"
         x-data="khqrPayment({
             initialSeconds: {{ $payment->time_remaining_seconds }},
             statusUrl: '{{ route('payment.status', $payment->id) }}'
         })">

        <!-- Payment Card -->
        <div class="bg-white border border-black/15 shadow-xl overflow-hidden relative">
            <!-- Top KHQR Header Bar -->
            <div class="bg-[#E21A1A] px-6 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/payments/khqr-logo.svg') }}" alt="KHQR" class="h-6">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase font-medium tracking-wider opacity-90">Bakong</span>
                </div>
            </div>

            <div class="p-6 text-center">
                <!-- Merchant & Order Details -->
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-brand-black/60">
                        @lang('store.merchant_name')
                    </p>
                    <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black mt-0.5">
                        RESET Store
                    </h2>
                    <p class="text-xs text-brand-black/70 mt-1">
                        @lang('store.payment_order_number'): <span class="font-mono font-bold text-brand-black">#{{ $order->number }}</span>
                    </p>
                </div>

                <!-- Price Amounts (Dual KHR & USD) -->
                <div class="mb-6 p-3.5 bg-brand-surface border border-black/10 rounded-sm">
                    @php
                        $rate = (int) ($order->exchange_rate ?: 4100);
                        if ($order->currency === 'KHR') {
                            $khrAmount = (float) ($order->total_charged_currency_amount ?: $order->total_cents);
                            $usdAmount = number_format($rate > 0 ? $khrAmount / $rate : 0, 2);
                        } else {
                            $usdAmount = number_format((float) $order->total_charged_currency_amount, 2);
                            $khrAmount = (int) round(((float) $order->total_charged_currency_amount) * $rate);
                        }
                    @endphp
                    @if($order->currency === 'KHR')
                        <div class="flex items-baseline justify-center gap-2">
                            <span class="text-3xl font-display font-bold text-brand-black">
                                {{ number_format($khrAmount, 0) }} ៛
                            </span>
                            <span class="text-xs uppercase font-semibold text-brand-black/50">KHR</span>
                        </div>
                        <div class="text-xs font-semibold text-brand-black/70 mt-1">
                            ≈ ${{ $usdAmount }} USD
                            <span class="text-[10px] text-brand-black/40 font-normal">({{ $rate }} ៛/$)</span>
                        </div>
                    @else
                        <div class="flex items-baseline justify-center gap-2">
                            <span class="text-3xl font-display font-bold text-brand-black">
                                ${{ $usdAmount }}
                            </span>
                            <span class="text-xs uppercase font-semibold text-brand-black/50">USD</span>
                        </div>
                        <div class="text-xs font-semibold text-brand-black/70 mt-1">
                            ≈ {{ number_format($khrAmount, 0) }} ៛ KHR
                            <span class="text-[10px] text-brand-black/40 font-normal">({{ $rate }} ៛/$)</span>
                        </div>
                    @endif
                </div>

                <!-- QR Code Box / Expired Overlay -->
                <div class="relative inline-block mx-auto p-4 bg-white border-2 border-dashed border-black/20 rounded-md">
                    <!-- Live QR Display -->
                    <div class="w-64 h-64 mx-auto flex items-center justify-center [&>svg]:w-full [&>svg]:h-full [&>svg]:block" :class="{ 'opacity-10 blur-sm pointer-events-none': isExpired }">
                        {!! $qrSvg !!}
                    </div>

                    <!-- Expired Overlay -->
                    <div x-show="isExpired" x-cloak class="absolute inset-0 bg-white/95 flex flex-col items-center justify-center p-6 text-center">
                        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-tight text-brand-black mb-1">
                            @lang('store.payment_status_expired')
                        </h3>
                        <p class="text-xs text-brand-black/60 mb-4">
                            @lang('store.payment_expired_desc')
                        </p>
                        <form action="{{ route('payment.regenerate', $order->number) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-5 py-2.5 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                                @lang('store.payment_regenerate_qr')
                            </button>
                        </form>
                    </div>

                    <!-- Paid Overlay -->
                    <div x-show="isPaid" x-cloak class="absolute inset-0 bg-white/95 flex flex-col items-center justify-center p-6 text-center">
                        <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mb-3 animate-bounce">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold uppercase tracking-tight text-green-700 mb-1">
                            @lang('store.payment_status_paid')
                        </h3>
                    </div>
                </div>

                <!-- Live Status & Countdown Indicator -->
                <div class="mt-4" x-show="!isExpired && !isPaid">
                    <div class="flex items-center justify-center gap-2 text-xs font-medium text-brand-black/70">
                        <span class="w-2 h-2 rounded-full bg-brand-orange animate-ping"></span>
                        <span>@lang('store.payment_status_waiting')</span>
                    </div>

                    <!-- Countdown -->
                    <div class="mt-2 text-xs font-mono font-bold text-brand-black">
                        @lang('store.payment_expires_in') <span x-text="formattedCountdown" class="text-brand-orange">{{ sprintf('%02d:%02d', floor($payment->time_remaining_seconds / 60), $payment->time_remaining_seconds % 60) }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 space-y-3">
                    <!-- Mobile Banking Deeplink -->
                    <a href="{{ $deeplinkUrl }}" class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        @lang('store.payment_open_banking')
                    </a>

                    <!-- Download QR -->
                    <a href="{{ route('payment.download', $payment->id) }}" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 border border-black/20 text-brand-black text-xs font-bold uppercase tracking-widest hover:bg-black/5 transition duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        @lang('store.payment_download_qr')
                    </a>
                </div>

                <!-- Dev Mock Simulation Panel (Visible only when BAKONG_MOCK=true) -->
                @if(config('bakong.mock'))
                    <div class="mt-6 p-4 bg-amber-50 border border-amber-300 rounded text-left">
                        <div class="flex items-center gap-1.5 text-amber-800 text-xs font-bold uppercase tracking-wider mb-1">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Dev / Test Mode
                        </div>
                        <p class="text-[11px] text-amber-700 leading-normal mb-3">
                            Running in local mock environment. You can test automatic polling or click below to simulate an instant scan and payment confirmation.
                        </p>
                        <form action="{{ route('payment.simulate', $payment->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold uppercase tracking-wider transition">
                                ⚡ Simulate Instant Payment
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Certified Footer Notice -->
            <div class="bg-brand-surface px-6 py-3 border-t border-black/10 text-center">
                <p class="text-[10px] text-brand-black/60 uppercase tracking-wider flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5 text-green-600 inline" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
                    </svg>
                    @lang('store.payment_security_note')
                </p>
            </div>
        </div>

        <!-- Instructions Accordion / Help -->
        <div class="mt-6 text-center text-xs text-brand-black/60">
            <p>
                @lang('store.payment_instructions')
            </p>
            <p class="mt-3">
                @lang('store.payment_supported_banks')
            </p>
        </div>
    </div>
</div>
@endsection
