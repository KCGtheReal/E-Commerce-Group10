<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3.5 bg-brand-black border border-transparent rounded-none font-display font-bold text-xs text-white uppercase tracking-widest hover:bg-brand-orange focus:bg-brand-black active:bg-brand-black focus:outline-none focus:ring-1 focus:ring-brand-black transition duration-150 cursor-pointer shadow-xs']) }}>
    {{ $slot }}
</button>
