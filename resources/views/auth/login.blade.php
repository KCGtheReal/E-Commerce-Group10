<x-guest-layout>
    <!-- Header -->
    <div class="border-b border-black/10 pb-4 mb-6">
        <span class="text-[11px] font-display font-bold uppercase tracking-widest text-brand-accent block mb-1">
            RESET STUDIO • ACCOUNT
        </span>
        <h1 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.nav_login')
        </h1>
        <p class="text-xs text-brand-black/60 mt-1">
            @lang('store.auth_login_subtitle')
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(session('warning'))
        <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-xs rounded">
            {{ session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('store.checkout_email') . ' *'" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('store.password') . ' *'" />

            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />

            @if (Route::has('password.request'))
                <div class="mt-2 text-right">
                    <a class="text-[11px] uppercase tracking-wider font-semibold text-brand-black/60 hover:text-brand-accent underline transition" href="{{ route('password.request') }}">
                        @lang('store.auth_forgot_password')
                    </a>
                </div>
            @endif
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" class="border-black/30 text-brand-black focus:ring-brand-black rounded-none" name="remember">
                <span class="text-xs uppercase tracking-wider text-brand-black/70 font-medium">@lang('store.auth_remember_me')</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <x-primary-button class="w-full">
                @lang('store.nav_login') →
            </x-primary-button>
        </div>
    </form>

    <!-- Switch to Register -->
    <div class="border-t border-black/10 mt-6 pt-6 text-center text-xs text-brand-black/70">
        <span>@lang('store.auth_dont_have_account')</span>
        <a href="{{ route('register') }}" class="font-bold underline uppercase ml-1 hover:text-brand-accent transition">
            @lang('store.nav_register')
        </a>
    </div>
</x-guest-layout>
