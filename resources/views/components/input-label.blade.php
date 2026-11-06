@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-wider text-brand-black/70 mb-1']) }}>
    {{ $value ?? $slot }}
</label>
