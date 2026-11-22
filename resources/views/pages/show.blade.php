@extends('layouts.app')

@section('title', $page->name)

@section('content')
<div class="space-y-6">
    <!-- Page header -->
    <div class="relative overflow-hidden rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @if($page->coverUrl())
            <img src="{{ $page->coverUrl() }}" alt="" class="h-48 w-full object-cover sm:h-64">
        @else
            <div class="h-48 w-full bg-gradient-to-br from-fb-blue to-blue-700 dark:from-blue-900 dark:to-zinc-800 sm:h-64"></div>
        @endif

        <div class="px-4">
            <div class="-mt-14 flex items-end justify-between gap-3 sm:-mt-16">
                @if($page->avatarUrl())
                    <img src="{{ $page->avatarUrl() }}" alt="" class="h-28 w-28 shrink-0 rounded-full border-4 border-fb-surface object-cover dark:border-zinc-900">
                @else
                    <span class="flex h-28 w-28 shrink-0 items-center justify-center rounded-full border-4 border-fb-surface bg-fb-blue text-3xl font-bold text-white dark:border-zinc-900">{{ mb_substr($page->name, 0, 1) }}</span>
                @endif
            </div>

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold leading-tight">{{ $page->name }}</h1>
                        @if($page->is_verified)
                            <span class="text-fb-blue" title="{{ __('app.verified') }}" aria-label="{{ __('app.verified') }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 2.1 3.2-.4 1 3 3 1-.4 3.2L23 12l-2.1 2.4.4 3.2-3 1-1 3-3.2-.4L12 23l-2.4-2.1-3.2.4-1-3-3-1 .4-3.2L1 12l2.1-2.4-.4-3.2 3-1 1-3 3.2.4L12 2z"/><path d="M10.5 15.5l-3-3 1.4-1.4 1.6 1.6 3.9-3.9 1.4 1.4-5.3 5.3z" fill="#fff"/></svg>
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-fb-muted dark:text-zinc-400">
                        <span>{{ __('app.page') }}</span>
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1">
                            @if($page->privacy === 'public')
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1m-8-4V7a2 2 0 012-2h2a2 2 0 012 2v1"/></svg>
                            @endif
                            {{ __('app.page_privacy_'.$page->privacy) }}
                        </span>
                        <span aria-hidden="true">·</span>
                        <a href="{{ route('pages.members.index', $page) }}" class="hover:text-fb-blue hover:underline dark:hover:text-blue-400">{{ $page->members_count }} {{ __('app.page_followers') }}</a>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    @if($isMember)
                        <button type="button" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700" onclick="openLeavePageModal()">
                            {{ __('app.page_following') }}
                        </button>
                    @else
                        <form method="POST" action="{{ route('pages.members.join', $page) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-fb-blue px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover">
                                {{ __('app.join_page') }}
                            </button>
                        </form>
                    @endif

                    @if($page->canManage(auth()->user()))
                        <a href="{{ route('pages.edit', $page) }}" class="rounded-lg px-3 py-2 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('app.edit') }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex gap-1">
                <a href="{{ route('pages.show', $page) }}" class="border-b-2 border-fb-blue px-4 py-2 text-sm font-medium text-fb-blue dark:border-blue-400 dark:text-blue-400" aria-current="page">{{ __('app.page_posts') }}</a>
                <a href="{{ route('pages.members.index', $page) }}" class="border-b-2 border-transparent px-4 py-2 text-sm font-medium text-fb-muted hover:bg-zinc-100 dark:text-zinc-500 dark:hover:bg-zinc-800">{{ __('app.page_followers') }}</a>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[minmax(260px,340px)_minmax(0,1fr)]">
        <aside class="space-y-4 self-start lg:sticky lg:top-20">
            <section class="rounded-xl border border-fb-border bg-fb-surface p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-bold">{{ __('app.about') }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm text-fb-text dark:text-zinc-200">{{ $page->description ?: __('app.page') }}</p>
                <div class="mt-4 space-y-3 border-t border-fb-border pt-4 text-sm dark:border-zinc-800">
                    <div class="flex items-center gap-2 text-fb-muted dark:text-zinc-400"><span aria-hidden="true">◉</span><span>{{ __('app.page_privacy_'.$page->privacy) }}</span></div>
                    <div class="flex items-center gap-2 text-fb-muted dark:text-zinc-400"><span aria-hidden="true">♟</span><span>{{ $page->members_count }} {{ __('app.page_followers') }}</span></div>
                    <div class="flex items-center gap-2 text-fb-muted dark:text-zinc-400"><span aria-hidden="true">◷</span><span>{{ __('app.created') }} {{ $page->created_at->format('M Y') }}</span></div>
                    <a href="{{ route('pages.members.index', $page) }}" class="inline-flex font-semibold text-fb-blue hover:underline">{{ __('app.view_all_followers') }}</a>
                </div>
            </section>
        </aside>

        <section class="min-w-0 space-y-4">
            @if($page->canManage(auth()->user()))
                <form method="POST" action="{{ route('pages.posts.store', $page) }}" enctype="multipart/form-data" class="rounded-xl border border-fb-border bg-fb-surface p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    @csrf
                    <div class="flex gap-3">
                        @if($page->avatarUrl())<img src="{{ $page->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">@else<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-fb-blue font-bold text-white">{{ mb_substr($page->name, 0, 1) }}</span>@endif
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

            <div class="flex items-center justify-between px-1"><h2 class="text-xl font-bold">{{ __('app.page_posts') }}</h2><span class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.latest') }}</span></div>
            @forelse($posts as $post)
                <x-post-group-page :post="$post" :parent-model="$page" />
            @empty
                <div class="rounded-xl border border-fb-border bg-fb-surface px-4 py-12 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900"><p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_page_posts_yet') }}</p></div>
            @endforelse
            {{ $posts->links() }}
        </section>
    </div>
</div>

<!-- Leave Page Modal -->
<div id="leave-page-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="rounded-xl bg-fb-surface shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700">
            <h3 class="font-semibold">{{ __('app.page_unfollow') }}</h3>
        </div>
        <div class="p-4 text-sm text-fb-text dark:text-zinc-300">
            {{ __('app.page_left_confirm') }}
        </div>
        <div class="p-4 border-t border-fb-border dark:border-zinc-700 flex justify-end gap-2">
            <button type="button" onclick="closeLeavePageModal()" class="rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</button>
            <form method="POST" action="{{ route('pages.members.leave', $page) }}" class="inline">
                @csrf
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('app.page_unfollow') }}</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function openLeavePageModal() {
            document.getElementById('leave-page-modal')?.classList.remove('hidden');
        }
        function closeLeavePageModal() {
            document.getElementById('leave-page-modal')?.classList.add('hidden');
        }
    </script>
    @vite('resources/js/chunked-upload.js')
@endpush
@endsection
