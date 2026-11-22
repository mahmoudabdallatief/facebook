@extends('layouts.app')

@section('title', __('app.members').' · '.$group->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    <!-- Group Header -->
    <div class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="px-4 py-3 border-b border-fb-border dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('groups.show', $group) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    {{ __('app.back') }}
                </a>
            </div>
            <div class="flex items-center gap-2">
                @if($group->canManage(auth()->user()))
                    <a href="{{ route('groups.edit', $group) }}" class="rounded-lg px-3 py-1.5 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('app.edit') }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </a>
                @endif
            </div>
        </div>

        <div class="px-4 py-4">
            @php($isMember = $group->isMember(auth()->user()))
            <div class="flex items-center gap-3 mb-2">
                @if($group->avatarUrl())
                    <img src="{{ $group->avatarUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover border-4 border-fb-surface dark:border-zinc-950">
                @else
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-fb-blue border-4 border-fb-surface dark:border-zinc-950 text-3xl font-bold text-white">{{ mb_substr($group->name, 0, 1) }}</span>
                @endif
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold">{{ $group->name }}</h1>
                    <div class="flex items-baseline gap-2 text-sm">
                        <span class="text-fb-muted dark:text-zinc-500">{{ __('app.group') }}</span>
                        @if($group->is_verified)
                            <span class="text-xs text-fb-blue">{{ __('app.verified') }}</span>
                        @endif
                        <span class="text-xs px-1.5 py-0.5 rounded border border-fb-border text-fb-muted dark:border-zinc-700 dark:text-zinc-500">
                            {{ __('app.page_privacy_'.$group->privacy) }}
                        </span>
                    </div>
                    @if($group->description)
                        <p class="mt-1 text-sm text-fb-text dark:text-zinc-200 whitespace-pre-line line-clamp-2">{{ nl2br(e($group->description)) }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-2">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Members Filter Bar (Top) -->
    @php($activeRole = request('role'))
    <div class="flex overflow-x-auto space-x-2 pb-2 mb-6 scrollbar-hide border-b border-fb-border dark:border-zinc-800">
        <a href="{{ route('groups.members.index', $group) }}" @class([
            'px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
            'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' => ! $activeRole,
            'border-transparent text-fb-muted dark:text-zinc-400 hover:text-fb-blue dark:hover:text-blue-400' => $activeRole,
        ]) @if(! $activeRole) aria-current="page" @endif>{{ __('app.all') }}</a>
        <a href="{{ route('groups.members.index', $group).'?role=admin' }}" @class([
            'px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
            'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' => $activeRole === 'admin',
            'border-transparent text-fb-muted dark:text-zinc-400 hover:text-fb-blue dark:hover:text-blue-400' => $activeRole !== 'admin',
        ]) @if($activeRole === 'admin') aria-current="page" @endif>{{ __('app.admins') }}</a>
        <a href="{{ route('groups.members.index', $group).'?role=moderator' }}" @class([
            'px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
            'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' => $activeRole === 'moderator',
            'border-transparent text-fb-muted dark:text-zinc-400 hover:text-fb-blue dark:hover:text-blue-400' => $activeRole !== 'moderator',
        ]) @if($activeRole === 'moderator') aria-current="page" @endif>{{ __('app.moderators') }}</a>
        <a href="{{ route('groups.members.index', $group).'?role=member' }}" @class([
            'px-4 py-2 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
            'border-fb-blue text-fb-blue dark:border-blue-400 dark:text-blue-400' => $activeRole === 'member',
            'border-transparent text-fb-muted dark:text-zinc-400 hover:text-fb-blue dark:hover:text-blue-400' => $activeRole !== 'member',
        ]) @if($activeRole === 'member') aria-current="page" @endif>{{ __('app.role_member') }}</a>
    </div>

    <!-- Members List Card -->
    <div class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="divide-y divide-fb-border dark:divide-zinc-800">
            <div class="space-y-3 px-4 py-4">
                @forelse($members as $member)
                    <?php
                        $isAdmin = $group->user_id === $member->user_id || $member->role === 'admin';
                    $isModerator = $member->role === 'moderator';
                    $isOwner = $group->user_id === $member->user_id;
                    $isMe = auth()->user()->id === $member->user_id;
                    ?>
                    <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                        <div class="flex-shrink-0">
                            <a href="{{ route('profile.show', $member->user) }}">
                                @if($member->user->avatarUrl())
                                    <img src="{{ $member->user->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover border border-fb-border dark:border-zinc-700">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white border border-fb-border dark:border-zinc-700">{{ mb_substr($member->user->name, 0, 1) }}</span>
                                @endif
                            </a>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline gap-2 mb-0.5">
                                <a href="{{ route('profile.show', $member->user) }}" class="font-semibold text-fb-text dark:text-zinc-100 hover:underline line-clamp-1">{{ $member->user->name }}</a>
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
                            <div class="text-xs text-fb-muted dark:text-zinc-500">{{ ($isOwner ? $group->created_at : $member->joined_at)?->diffForHumans() }}</div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(auth()->user()->id === $group->user_id && !$isAdmin && !$isMe)
                                <form method="POST" action="{{ route('groups.members.update', ['group' => $group, 'user' => $member->user]) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <select name="role" onchange="this.form.requestSubmit()" class="text-xs rounded-lg border border-fb-border bg-white px-2 py-1 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue/50 dark:focus:ring-blue-500/30">
                                        <option value="moderator" {{ $member->role === 'moderator' ? 'selected' : '' }}>{{ __('app.role_moderator') }}</option>
                                        <option value="member" {{ $member->role === 'member' ? 'selected' : '' }}>{{ __('app.role_member') }}</option>
                                    </select>
                                </form>
                            @endif

                            @if((auth()->user()->id === $group->user_id || auth()->user()->id === $member->user_id) && !$isAdmin && !$isMe)
                                <form method="POST" action="{{ route('groups.members.remove', ['group' => $group, 'user' => $member->user]) }}" onsubmit="return confirm('{{ __('app.group_member_removed_confirm') }}')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-700 dark:hover:text-red-400 px-2 py-1">{{ __('app.remove') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <p class="text-sm text-fb-muted dark:text-zinc-500">{{ __('app.no_members_yet') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{ $members->links() }}

</div>

<!-- Leave Group Modal -->
<div id="leave-group-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-fb-surface rounded-xl shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
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

        // Member role change handled inline via onchange
    </script>
@endpush
@endsection
