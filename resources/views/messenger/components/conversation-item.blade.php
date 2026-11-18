@php
$otherUser = $conversation->other_user ?? $conversation->otherUser(auth()->user());
$lastMessage = $conversation->lastMessage;
$unreadCount = $conversation->unread_count ?? 0;
$isActive = request()->route('conversation')?->id === $conversation->id;
@endphp

<a href="{{ route('messenger.show', $conversation) }}"
   class="flex items-center gap-3 p-3 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors {{ $isActive ? 'bg-fb-blue/10 dark:bg-fb-blue/20' : '' }}"
   data-conversation-id="{{ $conversation->id }}">
    <div class="relative flex-shrink-0">
        @if($otherUser->avatarUrl())
            <img src="{{ $otherUser->avatarUrl() }}" alt="{{ $otherUser->name }}" class="w-11 h-11 rounded-full object-cover">
        @else
            <div class="w-11 h-11 rounded-full bg-fb-blue flex items-center justify-center text-white font-semibold">{{ mb_substr($otherUser->name, 0, 1) }}</div>
        @endif
        @if($otherUser->isOnline())
            <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-fb-surface dark:border-zinc-900 rounded-full"></span>
        @endif
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
            <h4 class="font-medium truncate">{{ $otherUser->name }}</h4>
            @if($lastMessage)
                <time class="text-xs text-fb-muted whitespace-nowrap" datetime="{{ $lastMessage->created_at->toISOString() }}">
                    {{ $lastMessage->created_at->diffForHumans() }}
                </time>
            @endif
        </div>
        <div class="flex items-center justify-between gap-2 mt-1">
            <p class="text-sm text-fb-muted truncate flex-1">
                @if($lastMessage)
                    @if($lastMessage->sender_id === auth()->id())
                        <span class="text-fb-muted/70">{{ __('messenger.you') }}: </span>
                    @endif
                    @if($lastMessage->type === 'image')
                        {{ __('messenger.image_message') }}
                    @elseif($lastMessage->type === 'video')
                        {{ __('messenger.video_message') }}
                    @else
                        {{ \Illuminate\Support\Str::limit($lastMessage->content ?? '', 50) }}
                    @endif
                @else
                    {{ __('messenger.no_messages_yet') }}
                @endif
            </p>
            @if($unreadCount > 0)
                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-fb-blue text-white text-xs font-medium flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
            @endif
        </div>
    </div>
</a>