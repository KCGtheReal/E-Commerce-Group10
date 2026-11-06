@extends('layouts.storefront', [
    'title' => __('store.account_profile') . ' — ' . __('store.brand_name'),
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-8">
        <h1 class="text-3xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.account_profile')
        </h1>
        <p class="text-xs text-brand-black/60 uppercase tracking-widest mt-1">@lang('store.account_profile_desc')</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">
        @include('accounts.partials.nav')

        <div class="flex-1 space-y-8">
            <div class="bg-white border border-black/10 p-6 sm:p-8">
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black border-b border-black/10 pb-3 mb-6">
                    @lang('store.account_personal_info')
                </h2>

                <form action="{{ route('account.profile.update') }}" method="POST" class="max-w-xl space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                            @lang('store.account_full_name')
                        </label>
                        <input type="text" name="name" id="name" required value="{{ old('name', $user->name) }}"
                                class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none uppercase">
                        @error('name')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                            @lang('store.checkout_email')
                        </label>
                        <input type="email" id="email" disabled value="{{ $user->email }}"
                                class="w-full text-xs px-3 py-2.5 border border-black/10 bg-black/5 text-brand-black/60 cursor-not-allowed">
                        <p class="text-[11px] text-brand-black/50 mt-1">@lang('store.account_email_permanent_note')</p>
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">
                            @lang('store.checkout_phone')
                        </label>
                        <input type="tel" name="phone" id="phone" placeholder="012 345 678" value="{{ old('phone', $user->phone) }}"
                                class="w-full text-xs px-3 py-2.5 border border-black/20 focus:border-brand-black focus:outline-none">
                        @error('phone')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="px-6 py-3 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                            @lang('store.account_save_changes')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
