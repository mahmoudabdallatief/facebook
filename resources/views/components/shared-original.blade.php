@props(['original', 'me'])

@if($original && $original->canView($me))
    <div class="mx-4 mb-3 overflow-hidden rounded-lg border border-fb-border dark:border-zinc-800">
        <div class="flex items-center gap-2 px-3 pt-3 pb-2">
            @if($original->isPagePost() && $original->page)
            <a href="{{ route('pages.show', $original->page) }}">
                @if($original->page->avatarUrl())
                    <img src="{{ $original->page->avatarUrl() }}" alt="" loading="lazy" decoding="async" class="h-8 w-8 rounded-full object-cover">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($original->page->name, 0, 1) }}</span>
                @endif
            </a>
            @else
            <a href="{{ route('profile.show', $original->user) }}">
                @if($original->user->avatarUrl())
                    <img src="{{ $original->user->avatarUrl() }}" alt="" loading="lazy" decoding="async" class="h-8 w-8 rounded-full object-cover">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($original->user->name, 0, 1) }}</span>
                @endif
            </a>
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex items-baseline gap-2 mb-0.5">
                    @if($original->isPagePost() && $original->page)
                        <a href="{{ route('pages.show', $original->page) }}" class="text-sm font-semibold hover:underline">{{ $original->page->name }}</a>
                    @else
                        <a href="{{ route('profile.show', $original->user) }}" class="text-sm font-semibold hover:underline">{{ $original->user->name }}</a>
                    @endif
                    @if($original->isGroupPost() && $original->group)
                        @php($role = $original->group->memberRole($original->user))
                        @if($role)
                            <span class="text-xs text-fb-blue">{{ $role === 'admin' ? __('app.role_admin') : ($role === 'moderator' ? __('app.role_moderator') : __('app.role_member')) }}</span>
                        @endif
                    @endif
                    @if($original->isGroupPost() && $original->group)
                        <a href="{{ route('groups.show', $original->group) }}" class="text-xs font-medium bg-zinc-100 px-1.5 py-0.5 rounded text-zinc-800 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                            {{ $original->group->name }}
                        </a>
                    @elseif($original->isPagePost() && $original->page)
                        <a href="{{ route('pages.show', $original->page) }}" class="text-xs font-medium bg-zinc-100 px-1.5 py-0.5 rounded text-zinc-800 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                            {{ $original->page->name }}
                        </a>
                    @endif
                </div>
                <div class="text-xs text-fb-muted dark:text-zinc-500">{{ $original->created_at->diffForHumans() }}</div>
            </div>
            <a href="{{ $original->getOriginalPostUrl() ?? $original->getShareUrl() }}" class="text-xs text-fb-muted hover:underline dark:text-zinc-400">{{ __('app.view_original') }}</a>
        </div>

        @if($original->body)
            <div class="px-3 pb-3 text-sm leading-relaxed">{{ nl2br(e($original->body)) }}</div>
        @endif

        @if($original->media->isNotEmpty())
            <x-media-grid :media="$original->media" height-class="h-56" />
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
