@extends('layouts.storefront', [
    'title' => __('store.error_500_title') . ' — ' . __('store.brand_name'),
])

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 text-center">
    <div class="max-w-md">
        <span class="text-xs font-mono font-bold tracking-widest text-red-600 uppercase block mb-2">
            System Error 500
        </span>
        <h1 class="text-4xl sm:text-5xl font-display font-bold uppercase tracking-tight text-brand-black mb-4">
            @lang('store.error_500_heading')
        </h1>
        <p class="text-xs sm:text-sm text-brand-black/60 mb-8 leading-relaxed">
            @lang('store.error_500_desc')
        </p>
        <div class="flex items-center justify-center gap-4">
            <a href="{{ route('home') }}" class="px-6 py-3 bg-brand-black text-white text-xs font-bold uppercase tracking-widest hover:bg-brand-orange transition">
                @lang('store.error_500_return_home')
            </a>
            <a href="{{ route('page.contact') }}" class="px-6 py-3 border border-black/20 text-brand-black text-xs font-bold uppercase tracking-widest hover:bg-black/5 transition">
                @lang('store.error_500_contact_support')
            </a>
        </div>
    </div>
</div>
@endsection
