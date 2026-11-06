<x-guest-layout>
    <!-- Header -->
    <div class="border-b border-black/10 pb-4 mb-6">
        <span class="text-[11px] font-display font-bold uppercase tracking-widest text-brand-accent block mb-1">
            RESET STUDIO • SECURITY
        </span>
        <h1 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black">
            Reset Password
        </h1>
        <p class="text-xs text-brand-black/60 mt-1">
            Choose a strong new password for your account.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('store.checkout_email') . ' *'" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('store.password') . ' *'" />
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" />
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

        <div class="pt-2">
            <x-primary-button class="w-full">
                Reset Password →
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
