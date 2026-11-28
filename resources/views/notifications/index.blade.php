@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold dark:text-white">{{ __('app.notifications') }}</h1>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="text-sm text-blue-600 dark:text-blue-400">{{ __('app.mark_all_read') }}</button>
            </form>
        </div>
        <div id="notifications-container" data-infinite-container="#notifications-container" class="space-y-2">
            @if($filtered->isNotEmpty())
                @include('notifications._items', ['notifications' => $filtered, 'notificationPosts' => $notificationPosts, 'notificationComments' => $notificationComments])
            @else
                <p class="text-gray-500 dark:text-gray-400">{{ __('app.no_notifications') }}</p>
            @endif
        </div>

        @if($notifications->hasMorePages())
            <div id="notifications-sentinel" data-infinite-sentinel data-url="{{ route('notifications.infinite') }}" data-next-page="{{ $notifications->currentPage() + 1 }}" data-has-more="true" data-container="#notifications-container" data-load-more-btn="#notifications-load-more-btn" class="h-4"></div>
            <div class="flex justify-center pt-2">
                <button id="notifications-load-more-btn" type="button" onclick="window.__infiniteLoaders['notifications-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
            </div>
        @else
            <div class="text-center py-4 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.no_more_posts') }}</div>
        @endif
    </div>
@endsection
