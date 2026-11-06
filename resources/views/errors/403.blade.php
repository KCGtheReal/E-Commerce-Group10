@extends('layouts.storefront', [
    'title' => 'Access Restricted — ' . __('store.brand_name'),
])

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-16 px-4 text-center">
    <div class="max-w-md bg-white border border-brand-border p-8 rounded-2xl shadow-sm">
        <div class="w-12 h-12 mx-auto rounded-full bg-amber-50 text-brand-accent flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m11-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <span class="text-[11px] font-mono font-bold tracking-widest text-brand-accent uppercase block mb-2">
            Error 403 &bull; Access Restricted
        </span>
        <h1 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black mb-3">
            Admin Access Required
        </h1>
        <p class="text-xs text-neutral-500 mb-6 leading-relaxed">
            @auth
                You are currently signed in as <strong>{{ auth()->user()->email }}</strong> (Customer account). Administrator privileges are required to view the management dashboard.
            @else
                You must be authenticated with an administrator account to access the management portal.
            @endauth
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="w-full sm:w-auto px-5 py-2.5 bg-brand-black text-white text-xs font-display font-bold uppercase tracking-wider rounded-lg hover:bg-neutral-800 transition">
                Return to Store
            </a>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full px-5 py-2.5 border border-brand-border text-brand-black text-xs font-display font-bold uppercase tracking-wider rounded-lg hover:bg-neutral-100 transition">
                        Sign Out & Switch
                    </button>
                </form>
            @else
                <a href="/admin/login" class="w-full sm:w-auto px-5 py-2.5 bg-brand-accent text-white text-xs font-display font-bold uppercase tracking-wider rounded-lg hover:bg-orange-700 transition">
                    Admin Sign In
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection
