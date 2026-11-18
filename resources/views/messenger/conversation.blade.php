@extends('layouts.app')

@section('title', $otherUser->name)

@section('content')
<div class="h-[calc(100vh-8rem)] min-h-[500px] flex flex-col">
    <header class="flex items-center gap-3 p-3 border-b border-fb-border dark:border-zinc-700 bg-fb-surface/95 dark:bg-zinc-900/95 backdrop-blur sticky top-0 z-10">
        <button type="button" onclick="goBackToList()" class="lg:hidden p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-fb-muted" aria-label="{{ __('messenger.back') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <div class="relative flex-shrink-0">
            @if($otherUser->avatarUrl())
                <img src="{{ $otherUser->avatarUrl() }}" alt="{{ $otherUser->name }}" class="w-10 h-10 rounded-full object-cover">
            @else
                <div class="w-10 h-10 rounded-full bg-fb-blue flex items-center justify-center text-white font-semibold">{{ mb_substr($otherUser->name, 0, 1) }}</div>
            @endif
            @if($otherUser->isOnline())
                <span id="user-presence-dot" class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-fb-surface dark:border-zinc-900 rounded-full" title="{{ __('messenger.online') }}"></span>
            @else
                <span id="user-presence-dot" class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-zinc-400 border-2 border-fb-surface dark:border-zinc-900 rounded-full" title="{{ __('messenger.last_seen', ['time' => $otherUser->last_seen_at?->diffForHumans() ?? '']) }}"></span>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold truncate">{{ $otherUser->name }}</h3>
            <p class="text-xs text-fb-muted" id="user-status" data-online-label="{{ __('messenger.online') }}" data-offline-label="{{ __('messenger.offline') }}" data-last-seen-template="{{ __('messenger.last_seen', ['time' => ':time']) }}">
                @if($otherUser->isOnline())
                    {{ __('messenger.online') }}
                @elseif($otherUser->last_seen_at)
                    {{ __('messenger.last_seen', ['time' => $otherUser->last_seen_at->diffForHumans()]) }}
                @else
                    {{ __('messenger.offline') }}
                @endif
            </p>
            <p class="text-xs text-fb-blue font-medium hidden" id="typing-indicator">
                <span class="typing-name"></span> {{ __('messenger.typing') }}
                <span class="typing-dots animate-pulse ml-1">…</span>
            </p>
        </div>
    </header>

    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
        @php($currentMessageDate = null)
        @foreach($messages as $message)
            @php($messageDate = $message->created_at->format('Y-m-d'))
            @if($messageDate !== $currentMessageDate)
                <div class="message-day-separator text-center text-xs text-fb-muted py-2" data-date="{{ $messageDate }}">
                    {{ $message->created_at->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'ar' ? 'l، j F Y' : 'l, F j, Y') }}
                </div>
                @php($currentMessageDate = $messageDate)
            @endif
            @include('messenger.components.message', ['message' => $message])
        @endforeach
    </div>

    <form id="message-form" class="p-3 border-t border-fb-border dark:border-zinc-700 bg-fb-surface/95 dark:bg-zinc-900/95 backdrop-blur sticky bottom-0 z-10" enctype="multipart/form-data">
        <div class="flex items-end gap-2">
            <div class="relative flex-shrink-0">
                <button type="button" id="attach-btn" class="p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-fb-muted" aria-label="{{ __('messenger.attach') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                </button>
                <input type="file" id="media-input" accept="image/*,video/*" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
            </div>

            <div class="flex-1 relative">
                <div id="media-previews" class="mb-2 flex flex-wrap gap-2"></div>
                <textarea id="message-input" name="content" placeholder="{{ __('messenger.message_placeholder') }}" rows="1" class="w-full px-4 py-2.5 pr-12 rounded-full border border-fb-border bg-fb-bg dark:bg-zinc-800 dark:border-zinc-700 focus:ring-2 focus:ring-fb-blue focus:border-transparent resize-none text-sm" aria-label="{{ __('messenger.message_placeholder') }}"></textarea>
            </div>

            <button type="submit" id="send-btn" class="flex-shrink-0 p-2.5 rounded-full bg-fb-blue text-white hover:bg-fb-blue-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed" aria-label="{{ __('messenger.send') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </div>

        <input type="hidden" name="media_ids[]" id="uploaded-media-ids">
    </form>
</div>

@endsection
