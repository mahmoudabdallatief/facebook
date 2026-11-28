@extends('layouts.app')

@section('title', __('app.pages'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">{{ __('app.pages') }}</h1>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="{{ route('pages.create') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-fb-blue px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.create_page') }}</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($pages as $page)
            <a href="{{ route('pages.show', $page) }}" class="card-grid-perf group block overflow-hidden rounded-xl border border-fb-border bg-fb-surface shadow-sm transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                @if($page->coverUrl())
                    <img src="{{ $page->coverUrl() }}" alt="" loading="lazy" decoding="async" fetchpriority="low" class="h-28 w-full bg-zinc-200 object-cover dark:bg-zinc-800">
                @else
                    <div class="h-28 bg-gradient-to-br from-fb-blue/80 to-blue-800 dark:from-blue-950 dark:to-zinc-800"></div>
                @endif
                <div class="flex items-start gap-3 p-4">
                    <div class="-mt-8 shrink-0 rounded-full bg-fb-surface p-1 dark:bg-zinc-900">
                        @if($page->avatarUrl())
                            <img src="{{ $page->avatarUrl() }}" alt="" loading="lazy" decoding="async" class="h-14 w-14 rounded-full object-cover">
                        @else
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-fb-blue text-xl font-bold text-white">{{ mb_substr($page->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2 mb-1">
                            <h2 class="text-lg font-semibold">{{ $page->name }}</h2>
                            @if($page->is_verified)
                                <span class="text-xs text-fb-blue">{{ __('app.verified') }}</span>
                            @endif
                        </div>
                        <div class="text-sm text-fb-muted dark:text-zinc-500">{{ __('app.page') }}</div>
                        @if($page->description)
                            <div class="text-sm text-fb-text dark:text-zinc-200 line-clamp-2">{{ nl2br(e($page->description)) }}</div>
                        @endif
                        <div class="mt-2 text-xs text-fb-muted dark:text-zinc-500">
                            {{ $page->members_count }} {{ __('app.page_followers') }} ·
                            {{ $page->admins_count }} {{ __('app.admins') }}
                        </div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full border border-fb-border text-fb-muted dark:border-zinc-700 dark:text-zinc-500">
                        {{ __('app.page_privacy_'.$page->privacy) }}
                    </span>
                </div>
            </a>
        @empty
            <div class="text-center py-12">
                <p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_pages_yet') }}</p>
            </div>
        @endforelse
    </div>

    {{ $pages->links() }}
</div>
@endsection
