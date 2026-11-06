@extends('layouts.storefront', [
    'title' => __('store.checkout_title') . ' — ' . __('store.brand_name'),
    'description' => 'Complete your shipping address and pay with Bakong KHQR.',
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-10">
        <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.checkout_title')
        </h1>
        @guest
            <p class="text-xs sm:text-sm text-brand-black/70 mt-2">
                @lang('store.checkout_guest_or_login')
                <a href="{{ route('login') }}" class="font-bold underline uppercase ml-1 hover:text-brand-orange">
                    @lang('store.nav_login')
                </a>
            </p>
        @endguest
    </div>

    @if(session('error'))
        <div class="mb-8 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form"
          x-data="{
              province: '{{ old('province', $selectedProvince) }}',
              shippingMethod: '{{ old('shipping_method', 'standard') }}',
              subtotalCents: {{ $totals['subtotal_cents'] }},
              discountCents: {{ $totals['discount_cents'] }},
              discountCode: '{{ $cart->discount?->code ?? '' }}',
              freeShippingQualified: {{ $totals['free_shipping_qualified'] ? 'true' : 'false' }},
              promoInput: '',
              promoLoading: false,
              promoMessage: '{{ session('success') && str_contains(session('success'), 'Discount') ? session('success') : '' }}',
              promoError: '',

              async applyPromo() {
                  const code = this.promoInput.trim();
                  if (!code) {
                      return;
                  }
                  this.promoLoading = true;
                  this.promoError = '';
                  this.promoMessage = '';

                  try {
                      const res = await fetch('{{ route('cart.discount.apply') }}', {
                          method: 'POST',
                          headers: {
                              'Content-Type': 'application/json',
                              'Accept': 'application/json',
                              'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                          },
                          body: JSON.stringify({ code: code })
                      });
                      const data = await res.json();
                      if (res.ok && data.success) {
                          this.discountCents = data.cart.discount_cents;
                          this.discountCode = data.cart.discount_code;
                          this.subtotalCents = data.cart.subtotal_cents;
                          this.promoMessage = data.message || '@lang('store.promo_code_applied')';
                          this.promoInput = '';
                      } else {
                          this.promoError = data.message || '@lang('store.promo_code_invalid')';
                      }
                  } catch (e) {
                      this.promoError = '@lang('store.promo_code_invalid')';
                  } finally {
                      this.promoLoading = false;
                  }
              },

              async removePromo() {
                  this.promoLoading = true;
                  this.promoError = '';
                  this.promoMessage = '';

                  try {
                      const res = await fetch('{{ route('cart.discount.remove') }}', {
                          method: 'DELETE',
                          headers: {
                              'Accept': 'application/json',
                              'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                          }
                      });
                      const data = await res.json();
                      if (res.ok && data.success) {
                          this.discountCents = 0;
                          this.discountCode = '';
                          this.subtotalCents = data.cart.subtotal_cents;
                          this.promoMessage = '@lang('store.promo_code_removed')';
                      } else {
                          this.promoError = data.message || 'Failed to remove discount.';
                      }
                  } catch (e) {
                      this.promoError = 'Failed to remove discount.';
                  } finally {
                      this.promoLoading = false;
                  }
              },

              get shippingFeeCents() {
                  if (this.shippingMethod === 'standard') {
                      return 0;
                  }
                  if (this.shippingMethod === 'express' && this.province === 'Phnom Penh') {
                      return 12000;
                  }
                  return 0;
              },
              get totalCents() {
                  return Math.max(0, this.subtotalCents - this.discountCents + this.shippingFeeCents);
              },
              formatCents(cents) {
                  const currency = window.RESET_STORE?.currency || 'KHR';
                  const rate = window.RESET_STORE?.exchangeRate || 4100;
                  if (currency === 'USD') {
                      return '$' + (cents / rate).toFixed(2);
                  }
                  return Number(cents).toLocaleString() + ' ៛';
              }
          }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <!-- Left Column: Shipping & Contact Form -->
            <div class="lg:col-span-7 space-y-10">
                <!-- Contact Information -->
                <div>
                    <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-6">
                        1. @lang('store.checkout_contact_info')
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                @lang('store.account_full_name') *
                            </label>
                            <input type="text" name="name" id="name" required
                                   value="{{ old('name', $defaultAddress?->full_name ?? $user?->name) }}"
                                   placeholder="e.g. SOKHA CHAN"
                                   class="w-full text-xs uppercase px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                @lang('store.checkout_email') *
                            </label>
                            <input type="email" name="email" id="email" required
                                   value="{{ old('email', $user?->email) }}"
                                   class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none @error('email') border-red-500 @enderror">
                            @error('email')
                                <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                @lang('store.checkout_phone') *
                            </label>
                            <input type="tel" name="phone" id="phone" required placeholder="012 345 678"
                                   value="{{ old('phone', $defaultAddress?->phone ?? $user?->phone) }}"
                                   class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none @error('phone') border-red-500 @enderror">
                            @error('phone')
                                <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div>
                    <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-6">
                        2. @lang('store.checkout_shipping_address')
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label for="province" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                @lang('store.checkout_province') *
                            </label>
                            <select name="province" id="province" required x-model="province"
                                    class="w-full text-xs uppercase px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none bg-white">
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}">{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="district" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                    @lang('store.checkout_district')
                                </label>
                                <input type="text" name="district" id="district" placeholder="e.g. Daun Penh / Chamkarmon"
                                       value="{{ old('district', $defaultAddress?->district) }}"
                                       class="w-full text-xs uppercase px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none">
                            </div>

                            <div>
                                <label for="commune" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                    @lang('store.checkout_commune')
                                </label>
                                <input type="text" name="commune" id="commune" placeholder="e.g. Phsar Thmei 1"
                                       value="{{ old('commune', $defaultAddress?->commune) }}"
                                       class="w-full text-xs uppercase px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label for="street_address" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                @lang('store.checkout_street_address') *
                            </label>
                            <input type="text" name="street_address" id="street_address" required placeholder="House #, Street 130, Near Central Market"
                                   value="{{ old('street_address', $defaultAddress?->street_address) }}"
                                   class="w-full text-xs uppercase px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none @error('street_address') border-red-500 @enderror">
                            @error('street_address')
                                <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                                @lang('store.checkout_notes')
                            </label>
                            <textarea name="notes" id="notes" rows="2" placeholder="Building gate code, call upon arrival, etc."
                                      class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Delivery Method Options -->
                <div>
                    <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-6">
                        3. @lang('store.checkout_delivery_method')
                    </h2>
                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 border border-black/20 cursor-pointer hover:border-black transition"
                               :class="{ 'border-brand-black ring-1 ring-black bg-brand-surface': shippingMethod === 'standard' }">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="shipping_method" value="standard" x-model="shippingMethod" class="text-brand-black focus:ring-brand-black">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-brand-black block">
                                        @lang('store.checkout_standard_delivery')
                                    </span>
                                    <span class="text-[11px] text-brand-black/60 block">
                                        @lang('store.standard_delivery_desc')
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-bold" x-text="formatCents(0)">
                                0 ៛
                            </span>
                        </label>

                        <label class="flex items-center justify-between p-4 border border-black/20 cursor-pointer hover:border-black transition"
                               x-show="province === 'Phnom Penh'"
                               :class="{ 'border-brand-black ring-1 ring-black bg-brand-surface': shippingMethod === 'express' }">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="shipping_method" value="express" x-model="shippingMethod" class="text-brand-black focus:ring-brand-black">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-brand-black block">
                                        @lang('store.checkout_express_delivery')
                                    </span>
                                    <span class="text-[11px] text-brand-black/60 block">
                                        @lang('store.express_delivery_desc')
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-bold" x-text="formatCents(12000)">{{ \App\Services\CurrencyService::formatCents(12000) }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Review & KHQR Confirmation -->
            <div class="lg:col-span-5">
                <div class="p-6 bg-brand-surface border border-black/10 space-y-6 sticky top-24">
                    <h2 class="text-lg font-display font-bold uppercase tracking-tight text-brand-black pb-3 border-b border-black/10">
                        @lang('store.checkout_order_summary')
                    </h2>

                    <!-- Items Preview -->
                    <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                        @foreach($cart->items as $item)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-14 bg-white border border-black/10 shrink-0 overflow-hidden">
                                        <img src="{{ $item->variant->product->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold uppercase tracking-tight truncate text-brand-black">{{ $item->variant->product->localized_name }}</p>
                                        <p class="text-brand-black/60 text-[11px] uppercase">{{ $item->variant->label }} × {{ $item->quantity }}</p>
                                    </div>
                                </div>
                                <span class="font-bold shrink-0">{{ $item->formatted_subtotal }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Promo / Discount Code in Order Summary -->
                    <div class="border-t border-black/10 pt-4">
                        <template x-if="discountCode">
                            <div class="p-3 bg-white border border-emerald-500/40 flex items-center justify-between gap-3 text-xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                        <span class="font-bold uppercase tracking-wider text-brand-black truncate" x-text="discountCode"></span>
                                        <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                            @lang('store.cart_discount')
                                        </span>
                                    </div>
                                    <p class="text-emerald-700 text-[11px] font-medium mt-0.5 truncate" x-text="promoMessage || '@lang('store.promo_code_applied')'"></p>
                                </div>
                                <button type="button"
                                        @click="removePromo()"
                                        :disabled="promoLoading"
                                        class="text-xs uppercase font-bold text-red-600 hover:text-red-700 hover:underline shrink-0 cursor-pointer disabled:opacity-50">
                                    <span x-show="!promoLoading">@lang('store.cart_promo_remove')</span>
                                    <span x-show="promoLoading" x-cloak>...</span>
                                </button>
                            </div>
                        </template>

                        <template x-if="!discountCode">
                            <div>
                                <label for="checkout_promo_code" class="block text-[11px] font-semibold uppercase tracking-wider text-brand-black/70 mb-1.5">
                                    @lang('store.cart_promo_placeholder')
                                </label>
                                <div class="flex">
                                    <input type="text"
                                           id="checkout_promo_code"
                                           x-model="promoInput"
                                           @keydown.enter.prevent="applyPromo()"
                                           placeholder="e.g. RESET10"
                                           class="flex-1 text-xs border border-black/20 px-3 py-2.5 uppercase tracking-wider focus:outline-none focus:border-brand-black bg-white"
                                           :disabled="promoLoading">
                                    <button type="button"
                                            @click="applyPromo()"
                                            :disabled="promoLoading || !promoInput.trim()"
                                            class="px-4 py-2.5 bg-brand-black text-white text-xs font-semibold uppercase tracking-widest hover:bg-brand-orange transition disabled:opacity-40 cursor-pointer flex items-center justify-center min-w-[76px]">
                                        <span x-show="!promoLoading">@lang('store.cart_promo_apply')</span>
                                        <span x-show="promoLoading" x-cloak>
                                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                                <template x-if="promoError">
                                    <p class="text-red-600 text-[11px] mt-1.5 font-medium" x-text="promoError"></p>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Price Calculations -->
                    <div class="space-y-3 text-xs uppercase tracking-wider border-t border-black/10 pt-4">
                        <div class="flex justify-between text-brand-black/70">
                            <span>@lang('store.cart_subtotal')</span>
                            <span class="font-semibold text-brand-black" x-text="formatCents(subtotalCents)">
                                {{ $totals['formatted_subtotal'] }}
                            </span>
                        </div>

                        <template x-if="discountCents > 0">
                            <div class="flex justify-between text-brand-orange font-semibold">
                                <span>@lang('store.cart_discount') (<span x-text="discountCode"></span>)</span>
                                <span x-text="'-' + formatCents(discountCents)">{{ $totals['formatted_discount'] }}</span>
                            </div>
                        </template>

                        <div class="flex justify-between text-brand-black/70">
                            <span>@lang('store.cart_shipping')</span>
                            <span class="font-semibold text-brand-black" x-text="shippingFeeCents === 0 ? '0 ៛' : formatCents(shippingFeeCents)">
                                {{ $totals['shipping_fee_cents'] === 0 ? '0 ៛' : $totals['formatted_shipping'] }}
                            </span>
                        </div>

                        <div class="border-t border-black/10 pt-3 flex justify-between items-baseline text-sm font-bold text-brand-black">
                            <span>@lang('store.cart_total')</span>
                            <span class="text-xl font-display" x-text="formatCents(totalCents)">
                                {{ $totals['formatted_total'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Bakong Notice & Security -->
                    <div class="p-4 bg-white border border-black/10 rounded space-y-2">
                        <div class="flex items-center gap-2">
                            <img src="{{ asset('images/payments/khqr-logo.svg') }}" alt="KHQR" class="h-5">
                            <span class="text-xs font-bold uppercase tracking-wider text-brand-black">
                                @lang('store.bakong_payment_badge')
                            </span>
                        </div>
                        <p class="text-[11px] text-brand-black/70 leading-relaxed">
                            @lang('store.checkout_stock_reserved_note')
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full flex items-center justify-center py-4 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition duration-200 shadow-sm cursor-pointer">
                        @lang('store.checkout_place_order') →
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
