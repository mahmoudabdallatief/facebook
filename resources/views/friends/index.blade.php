@extends('layouts.app')

@section('title', __('app.friends'))

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">{{ __('app.friends_and_following') }}</h1>

    @if($pendingIncoming->isNotEmpty())
        <x-card :title="__('app.friend_requests')">
            <div id="incoming-container" data-infinite-container="#incoming-container" class="divide-y divide-fb-border dark:divide-zinc-800">
                @include('friends._incoming', ['requests' => $pendingIncoming])
            </div>
            @if($pendingIncoming->hasMorePages())
                <div id="incoming-sentinel" data-infinite-sentinel data-url="{{ route('friends.infinite.requests.incoming') }}" data-next-page="{{ $pendingIncoming->currentPage() + 1 }}" data-has-more="true" data-container="#incoming-container" data-load-more-btn="#incoming-load-more-btn" class="h-4"></div>
                <div class="flex justify-center pt-2">
                    <button id="incoming-load-more-btn" type="button" onclick="window.__infiniteLoaders['incoming-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
                </div>
            @endif
        </x-card>
    @endif

    @if($pendingOutgoing->isNotEmpty())
        <x-card :title="__('app.sent_requests')">
            <div id="outgoing-container" data-infinite-container="#outgoing-container" class="divide-y divide-fb-border dark:divide-zinc-800">
                @include('friends._outgoing', ['requests' => $pendingOutgoing])
            </div>
            @if($pendingOutgoing->hasMorePages())
                <div id="outgoing-sentinel" data-infinite-sentinel data-url="{{ route('friends.infinite.requests.outgoing') }}" data-next-page="{{ $pendingOutgoing->currentPage() + 1 }}" data-has-more="true" data-container="#outgoing-container" data-load-more-btn="#outgoing-load-more-btn" class="h-4"></div>
                <div class="flex justify-center pt-2">
                    <button id="outgoing-load-more-btn" type="button" onclick="window.__infiniteLoaders['outgoing-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
                </div>
            @endif
        </x-card>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="__('app.your_friends')">
            @if($friendships->isEmpty())
                <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.no_friends_yet') }}</p>
            @else
                <div id="friends-container" data-infinite-container="#friends-container" class="divide-y divide-fb-border dark:divide-zinc-800">
                    @include('friends._friends', ['friendships' => $friendships, 'viewer' => auth()->user()])
                </div>
                @if($friendships->hasMorePages())
                    <div id="friends-sentinel" data-infinite-sentinel data-url="{{ route('friends.infinite.friends') }}" data-next-page="{{ $friendships->currentPage() + 1 }}" data-has-more="true" data-container="#friends-container" data-load-more-btn="#friends-load-more-btn" class="h-4"></div>
                    <div class="flex justify-center pt-2">
                        <button id="friends-load-more-btn" type="button" onclick="window.__infiniteLoaders['friends-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
                    </div>
                @endif
            @endif
        </x-card>

        <x-card :title="__('app.friend_suggestions')">
            @if($suggestions->isEmpty())
                <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.no_suggestions') }}</p>
            @else
                <div id="suggestions-container" data-infinite-container="#suggestions-container" class="divide-y divide-fb-border dark:divide-zinc-800">
                    @include('friends._suggestions', ['users' => $suggestions])
                </div>
                @if($suggestions->hasMorePages())
                    <div id="suggestions-sentinel" data-infinite-sentinel data-url="{{ route('friends.infinite.suggestions') }}" data-next-page="{{ $suggestions->currentPage() + 1 }}" data-has-more="true" data-container="#suggestions-container" data-load-more-btn="#suggestions-load-more-btn" class="h-4"></div>
                    <div class="flex justify-center pt-2">
                        <button id="suggestions-load-more-btn" type="button" onclick="window.__infiniteLoaders['suggestions-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
                    </div>
                @endif
            @endif
        </x-card>
    </div>
</div>
@endsection
