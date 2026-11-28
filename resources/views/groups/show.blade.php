@extends('layouts.app')

@section('title', $group->name)

@section('content')
<div class="space-y-6">
    <!-- Group header -->
    <div class="relative overflow-hidden rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @if($group->coverUrl())
            <img src="{{ $group->coverUrl() }}" alt="" loading="lazy" decoding="async" class="h-48 w-full bg-zinc-200 object-cover dark:bg-zinc-800 sm:h-64">
        @else
            <div class="h-48 w-full bg-gradient-to-br from-fb-blue to-blue-700 dark:from-blue-900 dark:to-zinc-800 sm:h-64"></div>
        @endif

        <div class="px-4">
            <div class="-mt-14 flex items-end justify-between gap-3 sm:-mt-16">
                @if($group->avatarUrl())
                    <img src="{{ $group->avatarUrl() }}" alt="" loading="lazy" decoding="async" class="h-28 w-28 shrink-0 rounded-full border-4 border-fb-surface object-cover dark:border-zinc-900">
                @else
                    <span class="flex h-28 w-28 shrink-0 items-center justify-center rounded-full border-4 border-fb-surface bg-fb-blue text-3xl font-bold text-white dark:border-zinc-900">{{ mb_substr($group->name, 0, 1) }}</span>
                @endif
            </div>

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold leading-tight">{{ $group->name }}</h1>
                        @if($group->is_verified)
                            <span class="text-fb-blue" title="{{ __('app.verified') }}" aria-label="{{ __('app.verified') }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 2.1 3.2-.4 1 3 3 1-.4 3.2L23 12l-2.1 2.4.4 3.2-3 1-1 3-3.2-.4L12 23l-2.4-2.1-3.2.4-1-3-3-1 .4-3.2L1 12l2.1-2.4-.4-3.2 3-1 1-3 3.2.4L12 2z"/><path d="M10.5 15.5l-3-3 1.4-1.4 1.6 1.6 3.9-3.9 1.4 1.4-5.3 5.3z" fill="#fff"/></svg>
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-fb-muted dark:text-zinc-400">
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            {{ __('app.group') }}
                        </span>
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1">
                            @if($group->privacy === 'public')
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1m-8-4V7a2 2 0 012-2h2a2 2 0 012 2v1"/></svg>
                            @endif
                            {{ __('app.page_privacy_'.$group->privacy) }}
                        </span>
                        <span aria-hidden="true">·</span>
                        <a href="{{ route('groups.members.index', $group) }}" class="hover:text-fb-blue hover:underline dark:hover:text-blue-400">{{ $group->members_count }} {{ __('app.members') }}</a>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if($isMember)
                        <button type="button" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700" onclick="openLeaveGroupModal()">
                            {{ __('app.group_left') }}
                        </button>
                    @else
                        <form method="POST" action="{{ route('groups.members.join', $group) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-fb-blue px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover">
                                {{ __('app.join_group') }}
                            </button>
                        </form>
                    @endif

                    @if($group->canManage(auth()->user()))
                        <a href="{{ route('groups.edit', $group) }}" class="rounded-lg px-3 py-2 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('app.edit') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex gap-1">
                <a href="{{ route('groups.show', $group) }}" class="border-b-2 border-fb-blue px-4 py-2 text-sm font-medium text-fb-blue dark:border-blue-400 dark:text-blue-400" aria-current="page">{{ __('app.group_posts') }}</a>
                <a href="{{ route('groups.members.index', $group) }}" class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-fb-muted hover:bg-zinc-100 dark:text-zinc-500 dark:hover:bg-zinc-800">{{ __('app.members') }}</a>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[minmax(260px,340px)_minmax(0,1fr)]">
        <aside class="space-y-4 self-start lg:sticky lg:top-20">
            <section class="rounded-xl border border-fb-border bg-fb-surface p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-bold">{{ __('app.about') }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm text-fb-text dark:text-zinc-200">{{ $group->description ?: __('app.group') }}</p>
                <div class="mt-4 space-y-3 border-t border-fb-border pt-4 text-sm dark:border-zinc-800">
                    <div class="flex items-center gap-2 text-fb-muted dark:text-zinc-400"><span aria-hidden="true">◉</span><span>{{ __('app.page_privacy_'.$group->privacy) }}</span></div>
                    <div class="flex items-center gap-2 text-fb-muted dark:text-zinc-400"><span aria-hidden="true">♟</span><span>{{ $group->members_count }} {{ __('app.members') }}</span></div>
                    <div class="flex items-center gap-2 text-fb-muted dark:text-zinc-400"><span aria-hidden="true">◷</span><span>{{ __('app.created') }} {{ $group->created_at->format('M Y') }}</span></div>
                    <a href="{{ route('groups.members.index', $group) }}" class="inline-flex font-semibold text-fb-blue hover:underline">{{ __('app.view_all_members') }}</a>
                </div>
            </section>
        </aside>

        <section class="min-w-0 space-y-4">
            @if(! $isMember && $group->privacy === 'private')
                <div class="rounded-xl border border-fb-border bg-fb-surface px-4 py-8 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="font-semibold">{{ __('app.private_group_members_only') }}</p>
                    <p class="mt-2 text-sm text-fb-muted dark:text-zinc-400">{{ __('app.join_private_group_to_see_posts') }}</p>
                </div>
            @else
            @if($isMember)
                <form method="POST" action="{{ route('groups.posts.store', $group) }}" enctype="multipart/form-data" class="rounded-xl border border-fb-border bg-fb-surface p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    @csrf
                    <div class="flex gap-3">
                        @if(auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">@else<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-fb-blue font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>@endif
                        <textarea name="body" rows="2" required maxlength="2000" placeholder="{{ __('app.write_post') }}" class="min-h-14 flex-1 resize-y rounded-2xl bg-zinc-100 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-fb-blue/40 dark:bg-zinc-800 dark:text-zinc-100">{{ old('body') }}</textarea>
                    </div>
                    @error('body')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('media')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    <div class="mt-3 flex items-center justify-between border-t border-fb-border pt-3 dark:border-zinc-800">
                        <label class="cursor-pointer rounded-lg px-3 py-2 text-sm font-medium text-fb-muted hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"><span aria-hidden="true">▧</span> {{ __('app.add_media') }}<input type="file" id="media" name="media[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska" class="sr-only"></label>
                        <button type="submit" class="rounded-lg bg-fb-blue px-5 py-2 text-sm font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.post') }}</button>
                    </div>
                    <p class="mt-2 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.media_hint') }}</p>
                </form>
            @endif

            <div class="flex items-center justify-between px-1"><h2 class="text-xl font-bold">{{ __('app.group_posts') }}</h2><span class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.latest') }}</span></div>
            <div id="group-posts-container" data-infinite-container="#group-posts-container" class="space-y-4">
                @if($posts->isNotEmpty())
                    @include('groups.posts._items', ['posts' => $posts, 'group' => $group])
                @else
                    <div class="rounded-xl border border-fb-border bg-fb-surface px-4 py-12 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_group_posts_yet') }}</p></div>
                @endif
            </div>
            @if($posts->hasMorePages())
                <div id="group-posts-sentinel" data-infinite-sentinel data-url="{{ route('groups.posts.infinite', $group) }}" data-next-page="{{ $posts->currentPage() + 1 }}" data-has-more="true" data-container="#group-posts-container" data-load-more-btn="#group-posts-load-more-btn" class="h-4"></div>
                <div class="flex justify-center pt-2">
                    <button id="group-posts-load-more-btn" type="button" onclick="window.__infiniteLoaders['group-posts-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
                </div>
            @else
                <div class="mt-4 text-center py-4 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.no_more_posts') }}</div>
            @endif
            @endif
        </section>
    </div>
</div>
<!-- Leave Group Modal -->
<div id="leave-group-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="rounded-xl bg-fb-surface shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700">
            <h3 class="font-semibold">{{ __('app.leave_group') }}</h3>
        </div>
        <div class="p-4 text-sm text-fb-text dark:text-zinc-300">
            {{ __('app.group_left_confirm') }}
        </div>
        <div class="p-4 border-t border-fb-border dark:border-zinc-700 flex justify-end gap-2">
            <button type="button" onclick="closeLeaveGroupModal()" class="rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</button>
            <form method="POST" action="{{ route('groups.members.leave', $group) }}" class="inline">
                @csrf
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('app.group_left') }}</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function openLeaveGroupModal() {
            document.getElementById('leave-group-modal')?.classList.remove('hidden');
        }
        function closeLeaveGroupModal() {
            document.getElementById('leave-group-modal')?.classList.add('hidden');
        }
    </script>
    @vite('resources/js/chunked-upload.js')
@endpush

@endsection
