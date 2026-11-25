@props(['post'])

@php($me = auth()->user())
<div class="px-4 py-3">
    <div class="flex items-center gap-3">
        <a href="{{ route('profile.show', $post->user) }}">
            @if($post->user->avatarUrl())
                <img src="{{ $post->user->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($post->user->name, 0, 1) }}</span>
            @endif
        </a>
        <div class="min-w-0 flex-1">
            <div class="flex items-baseline gap-2 mb-1">
                <a href="{{ route('profile.show', $post->user) }}" class="text-sm font-semibold text-fb-text dark:text-zinc-100 hover:underline">{{ $post->user->name }}</a>
                <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.shared_by_user', ['name' => $post->user->name]) }}</span>
            </div>
            @if($post->body)
                <div class="text-xs text-fb-text dark:text-zinc-200 leading-relaxed line-clamp-2">{{ nl2br(e($post->body)) }}</div>
            @endif
            @if($post->originalPost)
                <div class="mt-2 rounded-lg border border-fb-border bg-zinc-50 px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900/40">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-fb-muted dark:text-zinc-400">{{ __('app.shared_from') }}</span>
                        <a href="{{ $post->getOriginalPostUrl() }}" class="text-xs text-fb-muted dark:text-zinc-500 hover:underline">{{ __('app.view_original') }}</a>
                    </div>
                    <div class="text-sm text-fb-text dark:text-zinc-200 leading-relaxed line-clamp-2">{{ nl2br(e($post->originalPost->body ?? '')) }}</div>
                </div>
            @endif
            <div class="mt-1 text-xs text-fb-muted dark:text-zinc-500">{{ $post->created_at->diffForHumans() }}</div>
        </div>
    </div>
</div>
