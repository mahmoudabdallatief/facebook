@props(['post'])

@php($me = auth()->user())
<div class="px-4 py-3">
    <div class="flex items-center gap-3">
        @if($post->isPagePost() && $post->page)
            <a href="{{ route('pages.show', $post->page) }}">
                @if($post->page->avatarUrl())
                    <img src="{{ $post->page->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($post->page->name, 0, 1) }}</span>
                @endif
            </a>
        @else
            <a href="{{ route('profile.show', $post->user) }}">
                @if($post->user->avatarUrl())
                    <img src="{{ $post->user->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($post->user->name, 0, 1) }}</span>
                @endif
            </a>
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex items-baseline gap-2 mb-1">
                @if($post->isPagePost() && $post->page)
                    <a href="{{ route('pages.show', $post->page) }}" class="text-sm font-semibold text-fb-text dark:text-zinc-100 hover:underline">{{ $post->page->name }}</a>
                @else
                    <a href="{{ route('profile.show', $post->user) }}" class="text-sm font-semibold text-fb-text dark:text-zinc-100 hover:underline">{{ $post->user->name }}</a>
                @endif
                @if($post->isGroupPost() && $post->group)
                    <span class="text-xs text-fb-muted dark:text-zinc-500">·</span>
                    <a href="{{ route('groups.show', $post->group) }}" class="text-xs text-fb-blue hover:underline">{{ $post->group->name }}</a>
                @endif
            </div>
            <div class="text-xs text-fb-text dark:text-zinc-200 leading-relaxed line-clamp-2">{{ nl2br(e($post->body)) }}</div>
            <div class="mt-1 text-xs text-fb-muted dark:text-zinc-500">{{ $post->created_at->diffForHumans() }}</div>
        </div>
    </div>
</div>
