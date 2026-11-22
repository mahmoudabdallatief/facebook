@extends('layouts.app')

@section('title', __('app.post') . ' · ' . $group->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('app.group_posts') }}</h1>
        <a href="{{ route('groups.show', $group) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
            <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            {{ __('app.back') }}
        </a>
    </div>

    <x-post-group-page :post="$post" :parentModel="$group" />

    @if($post->isShare())
        <div class="rounded-xl border border-fb-border bg-fb-surface p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('groups.show', $post->group) }}">
                    @if($post->group->avatarUrl())
                        <img src="{{ $post->group->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                    @else
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($post->group->name, 0, 1) }}</span>
                    @endif
                </a>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('groups.show', $post->group) }}" class="font-semibold hover:underline">{{ $post->group->name }}</a>
                    <div class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.group') }}</div>
                </div>
            </div>
            <a href="{{ route('groups.posts.show', ['group' => $post->group, 'post' => $post->rootOriginalPost()]) }}" class="text-sm text-fb-blue hover:underline">{{ __('app.view_original') }}</a>
        </div>
    @endif
</div>
@endsection
