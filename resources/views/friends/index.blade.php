@extends('layouts.app')

@section('title', __('app.friends'))

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">{{ __('app.friends_and_following') }}</h1>

    @if($pendingIncoming->isNotEmpty())
        <x-card :title="__('app.friend_requests')">
            <div class="divide-y divide-fb-border dark:divide-zinc-800">
                @foreach($pendingIncoming as $req)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ route('profile.show', $req->user) }}" class="flex items-center gap-3 min-w-0">
                            @if($req->user->avatarUrl())
                                <img src="{{ $req->user->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($req->user->name, 0, 1) }}</span>
                            @endif
                            <span class="font-medium truncate">{{ $req->user->name }}</span>
                        </a>
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('friends.accept', $req) }}">@csrf
                                <button type="submit" class="rounded-lg bg-fb-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-fb-blue-hover">{{ __('app.accept') }}</button>
                            </form>
                            <form method="POST" action="{{ route('friends.reject', $req) }}">@csrf
                                <button type="submit" class="rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.reject') }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    @if($pendingOutgoing->isNotEmpty())
        <x-card :title="__('app.sent_requests')">
            <div class="divide-y divide-fb-border dark:divide-zinc-800">
                @foreach($pendingOutgoing as $req)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ route('profile.show', $req->friend) }}" class="flex items-center gap-3 min-w-0">
                            @if($req->friend->avatarUrl())
                                <img src="{{ $req->friend->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($req->friend->name, 0, 1) }}</span>
                            @endif
                            <span class="font-medium truncate">{{ $req->friend->name }}</span>
                        </a>
                        <form method="POST" action="{{ route('friends.cancel', $req) }}">@csrf
                            <button type="submit" class="rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel_request') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="__('app.your_friends')">
            @if($friends->isEmpty())
                <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.no_friends_yet') }}</p>
            @else
                <div class="divide-y divide-fb-border dark:divide-zinc-800">
                    @foreach($friends as $friend)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <a href="{{ route('profile.show', $friend) }}" class="flex items-center gap-3 min-w-0">
                                @if($friend->avatarUrl())
                                    <img src="{{ $friend->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($friend->name, 0, 1) }}</span>
                                @endif
                                <span class="font-medium truncate">{{ $friend->name }}</span>
                            </a>
                            <form method="POST" action="{{ route('friends.destroy', $friend) }}">@csrf @method('DELETE')
                                <button type="submit" class="rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.unfriend') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card :title="__('app.friend_suggestions')">
            @if($suggestions->isEmpty())
                <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.no_suggestions') }}</p>
            @else
                <div class="divide-y divide-fb-border dark:divide-zinc-800">
                    @foreach($suggestions as $person)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <a href="{{ route('profile.show', $person) }}" class="flex items-center gap-3 min-w-0">
                                @if($person->avatarUrl())
                                    <img src="{{ $person->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($person->name, 0, 1) }}</span>
                                @endif
                                <span class="font-medium truncate">{{ $person->name }}</span>
                            </a>
                            <form method="POST" action="{{ route('friends.send', $person) }}">@csrf
                                <button type="submit" class="rounded-lg bg-fb-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-fb-blue-hover">{{ __('app.add_friend') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>
@endsection