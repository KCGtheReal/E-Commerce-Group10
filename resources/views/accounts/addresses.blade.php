@extends('layouts.storefront', [
    'title' => __('store.account_addresses') . ' — ' . __('store.brand_name'),
])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    <div class="border-b border-black/10 pb-6 mb-8">
        <h1 class="text-3xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.account_addresses')
        </h1>
        <p class="text-xs text-brand-black/60 uppercase tracking-widest mt-1">@lang('store.account_addresses_desc')</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">
        @include('accounts.partials.nav')

        <div class="flex-1 space-y-10">
            <!-- Add New Address Form -->
            <div class="bg-white border border-black/10 p-6 sm:p-8" x-data="{ open: false }">
                <div class="flex items-center justify-between pb-4 border-b border-black/10">
                    <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black">
                        @lang('store.account_add_new_address')
                    </h2>
                    <button @click="open = !open" type="button" class="text-xs font-bold uppercase tracking-wider text-brand-orange hover:underline">
                        <span x-show="!open">@lang('store.account_add_address')</span>
                        <span x-show="open">@lang('store.account_hide')</span>
                    </button>
                </div>

                <div x-show="open" class="mt-6" x-cloak>
                    <form action="{{ route('account.addresses.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.account_full_name') *</label>
                            <input type="text" name="name" required placeholder="e.g. SOKHA CHAN" class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none uppercase">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.checkout_phone') *</label>
                                <input type="tel" name="phone" required placeholder="012 345 678" class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.checkout_province') *</label>
                                <select name="province" required class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none bg-white uppercase">
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov }}">{{ $prov }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.checkout_district')</label>
                                <input type="text" name="district" placeholder="Daun Penh" class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.checkout_commune')</label>
                                <input type="text" name="commune" placeholder="Phsar Thmei 1" class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none uppercase">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1">@lang('store.checkout_street_address') *</label>
                            <input type="text" name="street_address" required placeholder="House #, Street 130" class="w-full text-xs px-3 py-2 border border-black/20 focus:border-brand-black focus:outline-none uppercase">
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="is_default" value="1" id="is_default" class="text-brand-black focus:ring-brand-black">
                            <label for="is_default" class="text-xs font-medium text-brand-black/80 uppercase">@lang('store.account_set_as_default')</label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="px-6 py-2.5 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                                @lang('store.account_save_address')
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Saved Addresses -->
            <div>
                <h2 class="text-base font-display font-bold uppercase tracking-wider text-brand-black mb-4">
                    @lang('store.account_saved_addresses')
                </h2>

                @if($addresses->isEmpty())
                    <div class="bg-white border border-black/10 p-8 text-center text-sm text-brand-black/60">
                        @lang('store.account_no_addresses')
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($addresses as $addr)
                            <div class="bg-white border border-black/10 p-6 relative hover:border-black/30 transition">
                                @if($addr->is_default)
                                    <span class="absolute top-4 right-4 px-2 py-0.5 bg-brand-surface border border-black/10 text-[10px] font-bold uppercase tracking-wider text-brand-black">
                                        @lang('store.account_default_badge')
                                    </span>
                                @endif

                                <h3 class="font-bold uppercase text-xs text-brand-black mb-2">{{ $addr->full_name }}</h3>
                                <div class="text-xs text-brand-black/70 space-y-0.5">
                                    <p>{{ $addr->street_address }}</p>
                                    <p>{{ $addr->commune }} {{ $addr->district }}</p>
                                    <p>{{ $addr->province }}, Cambodia</p>
                                    <p class="pt-1 font-semibold text-brand-black">{{ $addr->phone }}</p>
                                </div>

                                <div class="mt-4 pt-4 border-t border-black/10 flex items-center justify-end">
                                    <form action="{{ route('account.addresses.destroy', $addr->id) }}" method="POST" onsubmit="return confirm('{{ __('store.confirm_delete_address') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline uppercase font-semibold">
                                            @lang('store.account_delete_address')
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
</div>
@endsection
