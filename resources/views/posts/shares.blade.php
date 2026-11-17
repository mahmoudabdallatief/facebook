@extends('layouts.app')

@section('title', __('app.shares_of'))

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('app.shares_of') }}</h1>
        <a href="{{ route('posts.show', $post) }}" class="text-sm text-fb-blue hover:underline">{{ __('app.view_original') }}</a>
    </div>

    @if($post->body || $post->media->isNotEmpty())
        <div class="rounded-xl border border-fb-border bg-fb-surface p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center gap-2 mb-2">
                @if($post->user->avatarUrl())
                    <img src="{{ $post->user->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($post->user->name, 0, 1) }}</span>
                @endif
                <span class="text-sm font-semibold">{{ $post->user->name }}</span>
                <span class="text-xs text-fb-muted dark:text-zinc-500">{{ $post->created_at->diffForHumans() }}</span>
            </div>
            @if($post->body)
                <div class="text-sm">{{ nl2br(e($post->body)) }}</div>
            @endif
        </div>
    @endif

    <div class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-fb-border px-4 py-3 text-sm font-semibold dark:border-zinc-800">{{ __('app.shared_by') }}</div>
        <div class="divide-y divide-fb-border dark:divide-zinc-800">
            @forelse($shares as $share)
                <div class="flex items-center gap-3 px-4 py-3">
                    <a href="{{ route('profile.show', $share->user) }}">
                        @if($share->user->avatarUrl())
                            <img src="{{ $share->user->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white">{{ mb_substr($share->user->name, 0, 1) }}</span>
                        @endif
                    </a>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('profile.show', $share->user) }}" class="font-semibold hover:underline">{{ $share->user->name }}</a>
                        <div class="text-xs text-fb-muted dark:text-zinc-500">{{ $share->created_at->diffForHumans() }}</div>
                        @if($share->body)
                            <div class="mt-1 text-sm text-fb-text dark:text-zinc-200">{{ nl2br(e($share->body)) }}</div>
                        @endif
                    </div>
                    <a href="{{ route('posts.show', $share) }}" class="rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">{{ __('app.view') }}</a>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-fb-muted dark:text-zinc-500">{{ __('app.no_shares_yet') }}</p>
            @endforelse
        </div>
    </div>

    {{ $shares->links() }}
</div>
@endsection