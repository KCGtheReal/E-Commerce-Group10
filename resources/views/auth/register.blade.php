<x-guest-layout>
    <!-- Header -->
    <div class="border-b border-black/10 pb-4 mb-6">
        <span class="text-[11px] font-display font-bold uppercase tracking-widest text-brand-accent block mb-1">
            RESET STUDIO • JOIN
        </span>
        <h1 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.nav_register')
        </h1>
        <p class="text-xs text-brand-black/60 mt-1">
            @lang('store.auth_register_subtitle')
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('store.account_full_name') . ' *'" />
            <x-text-input id="name" class="block w-full uppercase" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="SOKHA CHAN" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('store.checkout_email') . ' *'" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Phone Number -->
        <div>
            <x-input-label for="phone" :value="__('store.phone_delivery_required') . ' *'" />
            <x-text-input id="phone" class="block w-full" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" placeholder="012 345 678" />
            <p class="text-[11px] text-brand-black/50 mt-1">@lang('store.phone_delivery_help')</p>
            <x-input-error :messages="$errors->get('phone')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('store.password') . ' *'" />
            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('store.confirm_password') . ' *'" />
            <x-text-input id="password_confirmation" class="block w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <x-primary-button class="w-full">
                @lang('store.nav_register') →
            </x-primary-button>
        </div>
    </form>

    <!-- Switch to Login -->
    <div class="border-t border-black/10 mt-6 pt-6 text-center text-xs text-brand-black/70">
        <span>@lang('store.auth_already_have_account')</span>
        <a href="{{ route('login') }}" class="font-bold underline uppercase ml-1 hover:text-brand-accent transition">
            @lang('store.nav_login')
        </a>
    </div>
</x-guest-layout>
