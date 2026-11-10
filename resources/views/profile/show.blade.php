@extends('layouts.app')

@section('title', $profileUser->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card center>
        <div class="flex flex-col items-center gap-4">
            @if($profileUser->avatarUrl())
                <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}" class="h-24 w-24 rounded-full border-4 border-fb-surface object-cover dark:border-zinc-900">
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-fb-surface bg-fb-blue text-3xl font-bold text-white dark:border-zinc-900">{{ mb_substr($profileUser->name, 0, 1) }}</div>
            @endif

            <div class="text-center">
                <h1 class="text-2xl font-bold">{{ $profileUser->name }}</h1>
                @auth
                    @if(auth()->id() === $profileUser->id)
                        <a href="{{ route('profile.edit') }}" class="mt-1 inline-block text-sm text-fb-blue hover:underline">{{ __('app.edit_profile') }}</a>
                    @else
                        <div class="mt-3 flex items-center justify-center gap-2">
                            @php($me = auth()->user())
                            @php($fs = $me->friendshipWith($profileUser))
                            @if($fs && $fs->status === 'accepted')
                                <form method="POST" action="{{ route('friends.destroy', $profileUser) }}">@csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.unfriend') }}</button>
                                </form>
                            @elseif($fs && $fs->status === 'pending' && $fs->user_id === $me->id)
                                <form method="POST" action="{{ route('friends.cancel', $fs) }}">@csrf
                                    <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel_request') }}</button>
                                </form>
                            @elseif($fs && $fs->status === 'pending' && $fs->friend_id === $me->id)
                                <form method="POST" action="{{ route('friends.accept', $fs) }}">@csrf
                                    <button type="submit" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.accept') }}</button>
                                </form>
                                <form method="POST" action="{{ route('friends.reject', $fs) }}">@csrf
                                    <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.reject') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('friends.send', $profileUser) }}">@csrf
                                    <button type="submit" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.add_friend') }}</button>
                                </form>
                            @endif
                        </div>
                    @endif
                @endauth
            </div>

            @if($profileUser->bio)
                <div class="w-full max-w-md text-center text-fb-muted dark:text-zinc-400">{{ $profileUser->bio }}</div>
            @endif
        </div>
    </x-card>

    @auth
        @if(auth()->id() !== $profileUser->id)
            <x-card :title="__('app.about')" class="mt-4">
                <p class="text-fb-muted dark:text-zinc-400">{{ __('app.no_posts_yet') }}</p>
            </x-card>
        @endif
    @endauth

    <x-card :title="__('app.timeline')" class="mt-4">
        <div class="space-y-4">
            @forelse($profileUser->posts()->latest()->take(20)->get() as $post)
                <x-post :post="$post" />
            @empty
                <div class="text-center py-8">
                    <p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_posts_yet') }}</p>
                </div>
            @endforelse
        </div>
    </x-card>
</div>
@endsection