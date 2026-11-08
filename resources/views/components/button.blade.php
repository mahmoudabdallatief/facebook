@props(['type' => 'submit', 'variant' => 'primary'])
@php($styles = [
    'primary' => 'bg-fb-blue text-white hover:bg-fb-blue-hover focus:ring-fb-blue/30',
    'green' => 'bg-fb-green text-white hover:brightness-95 focus:ring-green-500/30',
    'secondary' => 'bg-zinc-200 text-zinc-900 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700 focus:ring-zinc-400/30',
])
<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-4 '.($styles[$variant] ?? $styles['primary'])]) }}>
    {{ $slot }}
</button>
