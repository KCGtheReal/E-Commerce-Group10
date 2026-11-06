@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-black/20 focus:border-brand-black focus:ring-1 focus:ring-brand-black text-xs px-3.5 py-2.5 rounded-none bg-white text-brand-black shadow-none']) }}>
