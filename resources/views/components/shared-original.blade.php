@props(['original', 'me'])

@if($original && $original->canView($me))
    <div class="mx-4 mb-3 overflow-hidden rounded-lg border border-fb-border dark:border-zinc-800">
        <div class="flex items-center gap-2 px-3 pt-3 pb-2">
            <a href="{{ route('profile.show', $original->user) }}">
                @if($original->user->avatarUrl())
                    <img src="{{ $original->user->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($original->user->name, 0, 1) }}</span>
                @endif
            </a>
            <div class="min-w-0 flex-1">
                <a href="{{ route('profile.show', $original->user) }}" class="text-sm font-semibold hover:underline">{{ $original->user->name }}</a>
                <div class="text-xs text-fb-muted dark:text-zinc-500">{{ $original->created_at->diffForHumans() }}</div>
            </div>
            <a href="{{ route('posts.show', $original) }}" class="text-xs text-fb-muted hover:underline dark:text-zinc-400">{{ __('app.view_original') }}</a>
        </div>

        @if($original->body)
            <div class="px-3 pb-3 text-sm leading-relaxed">{{ nl2br(e($original->body)) }}</div>
        @endif

        @if($original->media->isNotEmpty())
            <div class="grid gap-1 {{ $original->media->count() === 1 ? 'grid-cols-1' : ($original->media->count() === 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3') }}">
                @foreach($original->media as $media)
                    @if($media->type === 'video')
                        <video src="{{ $media->url }}" controls class="h-56 w-full object-cover"></video>
                    @else
                        <img src="{{ $media->url }}" alt="" class="h-56 w-full object-cover">
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@else
    <div class="mx-4 mb-3 rounded-lg border border-fb-border bg-fb-bg/50 px-3 py-3 text-sm text-fb-muted dark:border-zinc-800 dark:bg-zinc-950/40 dark:text-zinc-400">
        {{ __('app.shared_content_unavailable') }}
        @if($original)
            · <a href="{{ route('posts.show', $original) }}" class="text-fb-blue hover:underline">{{ __('app.view_original') }}</a>
        @endif
    </div>
@endif