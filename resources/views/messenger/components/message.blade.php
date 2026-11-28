@php
$isOwn = $message->sender_id === auth()->id();
$alignClass = $isOwn ? 'justify-end' : 'justify-start';
$bubbleClass = $isOwn ? 'bg-fb-blue text-white rounded-bl-none' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-br-none';
$timeClass = $isOwn ? 'text-blue-100' : 'text-fb-muted';
@endphp

<div class="flex {{ $alignClass }}" id="message-{{ $message->id }}" data-message-id="{{ $message->id }}">
    <div class="max-w-[70%] {{ $bubbleClass }} rounded-2xl px-4 py-2 shadow-sm">
        @if($message->media->isNotEmpty())
            <div class="grid gap-2 {{ $message->media->count() > 1 ? 'grid-cols-2' : '' }} mb-2">
                @foreach($message->media as $media)
                    @if($media->type === 'image')
                        <a href="{{ $media->url }}" target="_blank" rel="noopener noreferrer" class="block rounded-lg overflow-hidden">
                            <img src="{{ $media->url }}" alt="" class="w-full h-auto max-h-64 object-cover">
                        </a>
                    @elseif($media->type === 'video')
                        <button type="button" data-video-facade data-video-src="{{ $media->url }}" data-video-type="{{ $media->metadata['mime_type'] ?? 'video/mp4' }}" class="w-full max-h-64 aspect-video rounded-lg bg-black text-white" aria-label="{{ __('messenger.js.play_video') }}">▶</button>
                    @endif
                @endforeach
            </div>
        @endif

        @if($message->content)
            <p class="whitespace-pre-wrap break-words">{{ $message->content }}</p>
        @endif

        <div class="flex items-center justify-end gap-1.5 mt-1.5">
            <time class="text-[10px] {{ $timeClass }}" datetime="{{ $message->created_at->toISOString() }}">
                {{ $message->created_at->locale(app()->getLocale())->translatedFormat('h:i A') }}
            </time>
            @if($isOwn)
                @if($message->isRead())
                    <svg class="w-4 h-4 {{ $timeClass }}" fill="currentColor" viewBox="0 0 24 24" data-status="read" title="{{ __('messenger.read') }}"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12 6.07 13.41 11.66 19l12.66-12.66z"/></svg>
                @else
                    <svg class="w-4 h-4 {{ $timeClass }}" fill="currentColor" viewBox="0 0 24 24" data-status="delivered" title="{{ __('messenger.delivered') }}"><path d="M18 7l-1.41-1.41-6.34 6.34 1.41 1.41L18 7zm4.24-1.41L11.66 16.17 7.48 12 6.07 13.41 11.66 19l12.66-12.66z"/></svg>
                @endif
            @endif
        </div>
    </div>
</div>
