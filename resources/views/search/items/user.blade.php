@props(['user'])

@php($me = auth()->user())
@php($alreadyFriend = $me ? $me->isFriendWith($user) : false)
@php($pending = $me ? ($me->hasPendingRequestTo($user) || $me->hasPendingRequestFrom($user)) : false)
<div class="px-4 py-3">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('profile.show', $user) }}" class="flex items-center gap-3 min-w-0">
            @if($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white">{{ mb_substr($user->name, 0, 1) }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <span class="block font-medium text-fb-text dark:text-zinc-100 hover:underline">{{ $user->name }}</span>
                <span class="block text-xs text-fb-muted dark:text-zinc-500">{{ $user->email }}</span>
            </div>
        </a>
        <div class="shrink-0">
            @if($me && !$me->id === $user->id && !$alreadyFriend && !$pending)
                <form method="POST" action="{{ route('friends.send', $user) }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-lg bg-fb-blue px-3 py-1.5 text-xs font-medium text-white hover:bg-fb-blue-hover">{{ __('app.add_friend') }}</button>
                </form>
            @elseif($alreadyFriend)
                <span class="rounded-lg bg-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">{{ __('app.already_friends') }}</span>
            @elseif($pending)
                <span class="rounded-lg bg-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100">{{ __('app.friend_request_pending') }}</span>
            @else
                <span class="hidden"></span>
            @endif
        </div>
    </div>
</div>
