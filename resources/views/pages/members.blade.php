@extends('layouts.app')

@section('title', __('app.page_followers').' · '.$page->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    <!-- Page summary -->
    <div class="overflow-hidden rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between border-b border-fb-border px-4 py-3 dark:border-zinc-800">
            <a href="{{ route('pages.show', $page) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                {{ __('app.back') }}
            </a>
            @if($page->canManage(auth()->user()))
                <a href="{{ route('pages.edit', $page) }}" class="rounded-lg px-3 py-1.5 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('app.edit') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </a>
            @endif
        </div>

        <div class="px-4 py-4">
            @php
                $isMember = $page->isMember(auth()->user());
            @endphp
            <div class="flex items-center gap-3">
                @if($page->avatarUrl())
                    <img src="{{ $page->avatarUrl() }}" alt="" class="h-14 w-14 shrink-0 rounded-full border-4 border-fb-surface object-cover dark:border-zinc-900">
                @else
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full border-4 border-fb-surface bg-fb-blue text-3xl font-bold text-white dark:border-zinc-900">{{ mb_substr($page->name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-xl font-bold">{{ $page->name }}</h1>
                    <div class="flex flex-wrap items-baseline gap-2 text-sm">
                        <span class="text-fb-muted dark:text-zinc-500">{{ __('app.page') }}</span>
                        @if($page->is_verified)
                            <span class="text-xs text-fb-blue">{{ __('app.verified') }}</span>
                        @endif
                        <span class="rounded border border-fb-border px-1.5 py-0.5 text-xs text-fb-muted dark:border-zinc-700 dark:text-zinc-500">{{ __('app.page_privacy_'.$page->privacy) }}</span>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Members filter bar -->
    <div class="flex gap-1 overflow-x-auto border-b border-fb-border dark:border-zinc-800">
        <a href="{{ route('pages.members.index', $page) }}" class="whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium {{ !request()->filled('role') ? 'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-fb-muted hover:text-fb-blue dark:text-zinc-400 dark:hover:text-blue-400' }}">{{ __('app.all') }}</a>
        <a href="{{ route('pages.members.index', $page).'?role=admin' }}" class="whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium {{ request('role') === 'admin' ? 'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-fb-muted hover:text-fb-blue dark:text-zinc-400 dark:hover:text-blue-400' }}">{{ __('app.admins') }}</a>
        <a href="{{ route('pages.members.index', $page).'?role=moderator' }}" class="whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium {{ request('role') === 'moderator' ? 'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-fb-muted hover:text-fb-blue dark:text-zinc-400 dark:hover:text-blue-400' }}">{{ __('app.moderators') }}</a>
        <a href="{{ route('pages.members.index', $page).'?role=member' }}" class="whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium {{ request('role') === 'member' ? 'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-fb-muted hover:text-fb-blue dark:text-zinc-400 dark:hover:text-blue-400' }}">{{ __('app.role_member') }}</a>
    </div>

    <!-- Members list -->
    <div class="overflow-hidden rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="divide-y divide-fb-border dark:divide-zinc-800">
            @forelse($members as $member)
                @php
                    $isOwner = $page->user_id === $member->user_id;
                    $isAdmin = $isOwner || $member->role === 'admin';
                    $isModerator = $member->role === 'moderator';
                    $isMe = auth()->user()->id === $member->user_id;
                @endphp
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <a href="{{ route('profile.show', $member->user) }}" class="shrink-0">
                        @if($member->user->avatarUrl())
                            <img src="{{ $member->user->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full border border-fb-border object-cover dark:border-zinc-700">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-full border border-fb-border bg-fb-blue text-sm font-bold text-white dark:border-zinc-700">{{ mb_substr($member->user->name, 0, 1) }}</span>
                        @endif
                    </a>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <a href="{{ route('profile.show', $member->user) }}" class="font-semibold text-fb-text hover:underline dark:text-zinc-100">{{ $member->user->name }}</a>
                            @if($isMe)
                                <span class="text-xs text-fb-blue">{{ __('app.you') }}</span>
                            @endif
                            @if($isAdmin)
                                <span class="text-xs text-fb-blue">{{ $isOwner ? __('app.owner') : __('app.role_admin') }}</span>
                            @elseif($isModerator)
                                <span class="text-xs text-fb-blue">{{ __('app.role_moderator') }}</span>
                            @else
                                <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.role_member') }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-fb-muted dark:text-zinc-500">{{ ($isOwner ? $page->created_at : $member->joined_at)?->diffForHumans() }}</div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if(auth()->user()->id === $page->user_id && !$isAdmin && !$isMe)
                            <form method="POST" action="{{ route('pages.members.update', ['page' => $page, 'user' => $member->user]) }}" class="inline">
                                @csrf @method('PATCH')
                                <select name="role" onchange="this.form.requestSubmit()" class="rounded-lg border border-fb-border bg-white px-2 py-1 text-xs text-zinc-900 focus:ring-2 focus:ring-fb-blue/50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:ring-blue-500/30">
                                    <option value="moderator" {{ $member->role === 'moderator' ? 'selected' : '' }}>{{ __('app.role_moderator') }}</option>
                                    <option value="member" {{ $member->role === 'member' ? 'selected' : '' }}>{{ __('app.role_member') }}</option>
                                </select>
                            </form>
                        @endif

                        @if((auth()->user()->id === $page->user_id || auth()->user()->id === $member->user_id) && !$isAdmin && !$isMe)
                            <form method="POST" action="{{ route('pages.members.remove', ['page' => $page, 'user' => $member->user]) }}" onsubmit="return confirm('{{ __('app.page_member_removed_confirm') }}')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="rounded-lg px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 dark:hover:text-red-400">{{ __('app.remove') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-fb-muted dark:text-zinc-500">{{ __('app.no_members_yet') }}</div>
            @endforelse
        </div>
    </div>

    {{ $members->links() }}

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
@endpush
@endsection
