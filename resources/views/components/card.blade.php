@props(['title' => null, 'center' => false])
<div {{ $attributes->merge(['class' => 'rounded-xl border border-fb-border bg-fb-surface p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900'.($center ? ' text-center' : '')]) }}>
    @if($title)
        <h2 class="mb-4 text-lg font-semibold">{{ $title }}</h2>
    @endif
    {{ $slot }}
</div>
