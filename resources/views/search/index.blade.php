@extends('layouts.app')

@section('title', __('app.search.title'))

@section('content')
<div class="space-y-6">
    <!-- Search bar -->
    <div class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="p-4">
            <form method="GET" action="{{ route('search') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
                <div class="flex-1 min-w-0 w-full sm:w-auto">
                    <label for="search-input" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-1.5">
                        {{ __('app.search.title') }}
                    </label>
                    <div class="relative">
                        <input
                            id="search-input"
                            name="q"
                            value="{{ old('q', $query) }}"
                            placeholder="{{ __('app.search.placeholder') }}"
                            class="w-full rounded-lg border border-fb-border bg-fb-surface px-4 py-3 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue focus:border-fb-blue outline-none"
                            autocomplete="off"
                        >
                    </div>
                    @error('q')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-fb-blue px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover transition-colors">
                    {{ __('app.search.title') }}
                </button>
            </form>
        </div>
    </div>

    @if($query === '')
        <div class="text-center py-24">
            <div class="flex items-center justify-center text-6xl text-fb-muted dark:text-zinc-700 mb-4">🔍</div>
            <h2 class="text-xl font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.start_searching') }}</h2>
            <p class="mt-1 text-sm text-fb-muted dark:text-zinc-400 max-w-md mx-auto">{{ __('app.search.start_sub') }}</p>
        </div>
    @elseif(!$hasQuery)
        <div class="text-center py-24">
            <div class="flex items-center justify-center text-6xl text-fb-muted dark:text-zinc-700 mb-4">⚠️</div>
            <h2 class="text-xl font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.query_too_short') }}</h2>
            <p class="mt-1 text-sm text-fb-muted dark:text-zinc-400 max-w-md mx-auto">{{ __('app.search.query_too_short_sub', ['min' => $minQueryLength]) }}</p>
        </div>
    @else
        <!-- Filter tabs -->
        <div class="flex gap-1 overflow-x-auto pb-1">
            @foreach([
                ['key' => 'all', 'label' => __('app.search.all')],
                ['key' => 'people', 'label' => __('app.search.people')],
                ['key' => 'posts', 'label' => __('app.search.posts')],
                ['key' => 'groups', 'label' => __('app.search.groups')],
                ['key' => 'pages', 'label' => __('app.search.pages')],
                ['key' => 'shares', 'label' => __('app.search.shares')],
            ] as $tab)
                <a
                    href="{{ route('search', ['q' => $query, 'type' => $tab['key']]) }}"
                    class="whitespace-nowrap shrink-0 inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors border-b-2 @if($type === $tab['key']) border-[#0866ff] bg-fb-blue text-white font-semibold dark:border-blue-400 dark:bg-blue-600 dark:text-white @else border-transparent text-fb-muted hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 @endif"
                    style="@if($type === $tab['key']) background-color:#0866ff; color:#fff; border-bottom-color:#0866ff; @endif"
                    @if($type === $tab['key']) aria-current="page" @endif
                >
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>

        @if($type === 'all')
            <div class="space-y-6">
                @if($users?->isNotEmpty())
                    <div class="rounded-xl border border-fb-border bg-fb-surface dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                        <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.people') }}</h2>
                        </div>
                        <div class="divide-y divide-fb-border dark:divide-zinc-800">
                            @foreach($users as $user)
                                @include('search.items.user', ['user' => $user])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($groups?->isNotEmpty())
                    <div class="rounded-xl border border-fb-border bg-fb-surface dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                        <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.groups') }}</h2>
                        </div>
                        <div class="divide-y divide-fb-border dark:divide-zinc-800">
                            @foreach($groups as $group)
                                @include('search.items.group', ['item' => $group, 'url' => route('groups.show', $group)])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($pages?->isNotEmpty())
                    <div class="rounded-xl border border-fb-border bg-fb-surface dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                        <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.pages') }}</h2>
                        </div>
                        <div class="divide-y divide-fb-border dark:divide-zinc-800">
                            @foreach($pages as $page)
                                @include('search.items.group', ['item' => $page, 'url' => route('pages.show', $page)])
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($posts?->isNotEmpty())
                    <div class="rounded-xl border border-fb-border bg-fb-surface dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                        <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.posts') }}</h2>
                        </div>
                        <div class="divide-y divide-fb-border dark:divide-zinc-800">
                            @foreach($posts as $post)
                                <x-post-group-page :post="$post" />
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($shares?->isNotEmpty())
                    <div class="rounded-xl border border-fb-border bg-fb-surface dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                        <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-fb-text dark:text-zinc-100">{{ __('app.search.shares') }}</h2>
                        </div>
                        <div class="divide-y divide-fb-border dark:divide-zinc-800">
                            @foreach($shares as $post)
                                <x-post-group-page :post="$post" />
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($users?->isEmpty() && $groups?->isEmpty() && $pages?->isEmpty() && $posts?->isEmpty() && $shares?->isEmpty())
                    <div class="text-center py-16">
                        <p class="text-fb-muted dark:text-zinc-400">{{ __('app.search.no_results_for', ['query' => $query]) }}</p>
                    </div>
                @endif
            </div>
        @else
            <!-- Single type results -->
            @php
                $items = match ($type) {
                    'people' => $users,
                    'posts' => $posts,
                    'groups' => $groups,
                    'pages' => $pages,
                    'shares' => $shares,
                    default => null,
                };
                $title = match ($type) {
                    'people' => __('app.search.people'),
                    'posts' => __('app.search.posts'),
                    'groups' => __('app.search.groups'),
                    'pages' => __('app.search.pages'),
                    'shares' => __('app.search.shares'),
                    default => __('app.search.all'),
                };
            @endphp

            <div class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800">
                    <h2 class="text-lg font-semibold text-fb-text dark:text-zinc-100">
                        {{ $title }} <span class="text-sm font-normal text-fb-muted dark:text-zinc-500">({{ $items?->total() ?? 0 }})</span>
                    </h2>
                </div>
                @if($items?->isNotEmpty())
                    <div class="divide-y divide-fb-border dark:divide-zinc-800">
                        @foreach($items as $item)
                            @if(in_array($type, ['posts', 'shares']))
                                <x-post-group-page :post="$item" />
                            @elseif(in_array($type, ['groups', 'pages']))
                                @include('search.items.group', ['item' => $item, 'url' => $type === 'groups' ? route('groups.show', $item) : route('pages.show', $item)])
                            @else
                                @include('search.items.user', ['user' => $item])
                            @endif
                        @endforeach
                    </div>
                    <div class="px-4 py-3">
                        {{ $items->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <p class="text-fb-muted dark:text-zinc-400">{{ __('app.search.no_results_for', ['query' => $query]) }}</p>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
