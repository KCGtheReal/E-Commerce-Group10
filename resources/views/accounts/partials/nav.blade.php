<aside class="w-full lg:w-64 shrink-0">
    <div class="bg-brand-surface border border-black/10 p-6">
        <div class="border-b border-black/10 pb-4 mb-4">
            <h3 class="font-display font-bold uppercase tracking-tight text-brand-black text-base">
                {{ Auth::user()->name }}
            </h3>
            <p class="text-xs text-brand-black/60 truncate mt-0.5">{{ Auth::user()->email }}</p>
        </div>

        <nav class="space-y-1 text-xs uppercase font-semibold tracking-wider">
            <a href="{{ route('account.profile') }}"
               class="flex items-center gap-3 px-3 py-2.5 transition {{ request()->routeIs('account.profile') ? 'bg-brand-black text-white' : 'text-brand-black/70 hover:bg-black/5 hover:text-brand-black' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                @lang('store.account_profile')
            </a>

            <a href="{{ route('account.orders') }}"
               class="flex items-center gap-3 px-3 py-2.5 transition {{ request()->routeIs('account.orders*') ? 'bg-brand-black text-white' : 'text-brand-black/70 hover:bg-black/5 hover:text-brand-black' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                @lang('store.account_orders')
            </a>

            <a href="{{ route('account.addresses') }}"
               class="flex items-center gap-3 px-3 py-2.5 transition {{ request()->routeIs('account.addresses') ? 'bg-brand-black text-white' : 'text-brand-black/70 hover:bg-black/5 hover:text-brand-black' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                @lang('store.account_addresses')
            </a>

            <a href="{{ route('wishlist.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 transition {{ request()->routeIs('wishlist.index') ? 'bg-brand-black text-white' : 'text-brand-black/70 hover:bg-black/5 hover:text-brand-black' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                @lang('store.account_wishlist')
            </a>

            <div class="pt-4 border-t border-black/10">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-brand-black/60 hover:text-red-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        @lang('store.nav_logout')
                    </button>
                </form>
            </div>
        </nav>
    </div>
</aside>
