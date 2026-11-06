@extends('layouts.storefront', [
    'title' => __('store.nav_contact') . ' — ' . __('store.brand_name'),
    'description' => 'Get in touch with the RESET team for inquiries, bulk orders, or customer support.',
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20">
    <div class="border-b border-black/10 pb-8 mb-12 text-center">
        <h1 class="text-3xl sm:text-4xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.nav_contact')
        </h1>
        <p class="text-xs sm:text-sm text-brand-black/60 uppercase tracking-widest mt-2">
            @lang('store.contact_subtitle')
        </p>
    </div>

    @if(session('success'))
        <div class="max-w-4xl mx-auto mb-8 p-4 bg-green-50 border-l-4 border-green-500 text-green-800 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-12">
        <!-- Contact Info -->
        <div class="md:col-span-5 space-y-8 text-xs text-brand-black/80">
            <div>
                <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black mb-2">
                    @lang('store.contact_studio_title')
                </h2>
                <p>Street 178, Daun Penh</p>
                <p>@lang('store.contact_phnom_penh')</p>
                <p class="text-brand-black/60 mt-1">@lang('store.contact_hours')</p>
            </div>

            <div>
                <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black mb-2">
                    @lang('store.contact_channels_title')
                </h2>
                <div class="space-y-1.5">
                    <p><span class="font-bold text-brand-black">Telegram:</span> <a href="https://t.me/reset_store_kh" target="_blank" class="underline hover:text-brand-orange font-mono">@reset_store_kh</a></p>
                    <p><span class="font-bold text-brand-black">@lang('store.email'):</span> <a href="mailto:support@reset.store" class="underline hover:text-brand-orange">support@reset.store</a></p>
                    <p><span class="font-bold text-brand-black">@lang('store.phone'):</span> +855 23 999 123</p>
                </div>
            </div>

            <div class="p-4 bg-brand-surface border border-black/10">
                <span class="font-bold uppercase tracking-wider text-brand-black block mb-1">@lang('store.contact_fastest_title')</span>
                <p class="text-[11px] text-brand-black/70 leading-relaxed">
                    @lang('store.contact_fastest_desc')
                </p>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="md:col-span-7">
            <div class="bg-white border border-black/10 p-6 sm:p-8">
                <h2 class="text-sm font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-6">
                    @lang('store.contact_send_message')
                </h2>

                <form action="{{ route('page.contact.submit') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.contact_name') *</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}"
                               class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none uppercase @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.contact_email') *</label>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}"
                               class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.contact_subject')</label>
                        <input type="text" name="subject" id="subject" value="{{ old('subject') }}" placeholder="Order Inquiry, Sizing, Press, etc."
                               class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none uppercase">
                    </div>

                    <div>
                        <label for="message" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.contact_message') *</label>
                        <textarea name="message" id="message" rows="4" required placeholder="{{ __('store.contact_message_placeholder') }}"
                                  class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-red-600 text-[11px] mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                        @lang('store.contact_submit')
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
