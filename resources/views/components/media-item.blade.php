@props([
    'item',
    'heightClass' => 'h-64',
    'roundedClass' => '',
    'deferred' => false,
    'sizeLabel' => null,
    'durationLabel' => null,
])

@php
    $thumbnail = $item->metadata['thumbnail'] ?? null;
    $mimeType = $item->metadata['mime_type'] ?? 'video/mp4';
@endphp

@if($item->type === 'video')
    <div
        data-video-facade
        data-video-src="{{ $item->url }}"
        data-video-type="{{ $mimeType }}"
        data-video-class="{{ $heightClass }} w-full object-cover bg-black {{ $roundedClass }}"
        role="button"
        tabindex="0"
        aria-label="{{ __('app.click_to_play_video') }}"
        class="group/video relative flex h-full w-full cursor-pointer items-center justify-center bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 text-white select-none"
    >
        @if($thumbnail)
            <img
                @if($deferred) data-lazy-src="{{ asset($thumbnail) }}" @else src="{{ asset($thumbnail) }}" @endif
                alt=""
                loading="lazy"
                decoding="async"
                fetchpriority="low"
                class="absolute inset-0 h-full w-full object-cover opacity-80 transition group-hover/video:opacity-65"
            >
        @endif
        <div class="relative z-10 flex flex-col items-center gap-2 px-3 text-center">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-black/70 text-white shadow-lg ring-1 ring-white/30 transition group-hover/video:scale-105 group-hover/video:bg-fb-blue">
                <svg class="h-6 w-6 translate-x-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </span>
            <span class="rounded-full bg-black/65 px-2.5 py-0.5 text-xs font-medium text-white/95 backdrop-blur-sm">
                {{ __('app.click_to_play_video') }}
            </span>
        </div>
        <div class="pointer-events-none absolute bottom-2 left-2 z-10 flex items-center gap-1.5 text-[11px] font-medium text-white">
            <span class="rounded bg-black/70 px-1.5 py-0.5">{{ $durationLabel ?? __('app.video_label') }}</span>
            @if($sizeLabel)
                <span class="rounded bg-black/70 px-1.5 py-0.5">{{ $sizeLabel }}</span>
            @endif
        </div>
    </div>
@else
    <img
        @if($deferred) data-lazy-src="{{ $item->url }}" @else src="{{ $item->url }}" @endif
        alt=""
        loading="lazy"
        decoding="async"
        fetchpriority="low"
        class="h-full w-full object-cover {{ $roundedClass }}"
    >
@endif
