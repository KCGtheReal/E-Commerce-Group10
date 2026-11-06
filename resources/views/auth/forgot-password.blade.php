<x-guest-layout>
    <!-- Header -->
    <div class="border-b border-black/10 pb-4 mb-6">
        <span class="text-[11px] font-display font-bold uppercase tracking-widest text-brand-accent block mb-1">
            RESET STUDIO • RECOVERY
        </span>
        <h1 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black">
            @lang('store.auth_forgot_password')
        </h1>
        <p class="text-xs text-brand-black/60 mt-1 leading-relaxed">
            Enter your email address and we will send you a secure password reset link.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('store.checkout_email') . ' *'" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full">
                Email Password Reset Link →
            </x-primary-button>
        </div>
    </form>

    <div class="border-t border-black/10 mt-6 pt-6 text-center text-xs text-brand-black/70">
        <a href="{{ route('login') }}" class="font-bold underline uppercase hover:text-brand-accent transition">
            ← Back to @lang('store.nav_login')
        </a>
    </div>
</x-guest-layout>
