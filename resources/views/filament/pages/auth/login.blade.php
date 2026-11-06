<div>
    <!-- Header -->
    <div class="border-b border-black/10 dark:border-neutral-800 pb-4 mb-6">
        <span class="text-[11px] font-display font-bold uppercase tracking-widest text-brand-accent block mb-1">
            RESET STUDIO • ADMIN PORTAL
        </span>
        <h1 class="text-2xl font-display font-bold uppercase tracking-tight text-brand-black dark:text-neutral-100">
            ADMIN SIGN IN
        </h1>
        <p class="text-xs text-brand-black/60 dark:text-neutral-400 mt-1">
            Access restricted to authorized store staff & administrators.
        </p>
    </div>

    <!-- Livewire / Filament Login Form -->
    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</div>
