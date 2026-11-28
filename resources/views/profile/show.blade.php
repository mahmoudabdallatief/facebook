@extends('layouts.app')

@section('title', $profileUser->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card center>
        <div class="flex flex-col items-center gap-4">
            @if($profileUser->avatarUrl())
                <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}" class="h-24 w-24 rounded-full border-4 border-fb-surface object-cover dark:border-zinc-900">
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-fb-surface bg-fb-blue text-3xl font-bold text-white dark:border-zinc-900">{{ mb_substr($profileUser->name, 0, 1) }}</div>
            @endif

            <div class="text-center">
                <h1 class="text-2xl font-bold">{{ $profileUser->name }}</h1>
                @auth
                    @if(auth()->id() === $profileUser->id)
                        <a href="{{ route('profile.edit') }}" class="mt-1 inline-block text-sm text-fb-blue hover:underline">{{ __('app.edit_profile') }}</a>
                    @else
                        <div class="mt-3 flex items-center justify-center gap-2">
                            @php($me = auth()->user())
                            @if($me->hasBlocked($profileUser))
                                <form method="POST" action="{{ route('settings.unblock', $profileUser) }}" onsubmit="return confirm('{{ addslashes(__('app.unblock_confirm', ['name' => $profileUser->name])) }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.unblock') }}</button>
                                </form>
                            @else
                                @php($fs = $me->friendshipWith($profileUser))
                                @if($fs && $fs->status === 'accepted')
                                    <form method="POST" action="{{ route('friends.destroy', $profileUser) }}">@csrf @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.unfriend') }}</button>
                                    </form>
                                @elseif($fs && $fs->status === 'pending' && $fs->user_id === $me->id)
                                    <form method="POST" action="{{ route('friends.cancel', $fs) }}">@csrf
                                        <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel_request') }}</button>
                                    </form>
                                @elseif($fs && $fs->status === 'pending' && $fs->friend_id === $me->id)
                                    <form method="POST" action="{{ route('friends.accept', $fs) }}">@csrf
                                        <button type="submit" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.accept') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('friends.reject', $fs) }}">@csrf
                                        <button type="submit" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.reject') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('friends.send', $profileUser) }}">@csrf
                                        <button type="submit" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.add_friend') }}</button>
                                    </form>
                                @endif
                                <button type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700" onclick="openBlockModal({{ $profileUser->id }})">{{ __('app.block_user') }}</button>
                                <form id="block-form-{{ $profileUser->id }}" method="POST" action="{{ route('settings.block-profile', $profileUser) }}" class="hidden">
                                    @csrf
                                    <input type="hidden" name="_method" value="POST">
                                </form>
                            @endif
                        </div>
                    @endif
                @endauth
            </div>

            <!-- Block Confirmation Modal -->
            <div id="block-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
                <div class="bg-fb-surface rounded-xl shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
                    <div class="p-4 border-b border-fb-border dark:border-zinc-700">
                        <h3 class="font-semibold">{{ __('app.block_user_title') }}</h3>
                    </div>
                    <div class="p-4 text-sm text-fb-text dark:text-zinc-300" id="block-modal-text">
                    </div>
                    <div class="p-4 border-t border-fb-border dark:border-zinc-700 flex justify-end gap-2">
                        <button type="button" onclick="closeBlockModal()" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600">{{ __('app.cancel') }}</button>
                        <button type="button" onclick="confirmBlock()" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('app.block_user') }}</button>
                    </div>
                </div>
            </div>

            @if($profileUser->bio)
                <div class="w-full max-w-md text-center text-fb-muted dark:text-zinc-400">{{ $profileUser->bio }}</div>
            @endif
        </div>
    </x-card>

    @auth
        {{-- @if(auth()->id() !== $profileUser->id)
            <x-card :title="__('app.about')" class="mt-4">
                <p class="text-fb-muted dark:text-zinc-400">{{ __('app.no_posts_yet') }}</p>
            </x-card>
        @endif --}}
    @endauth

    <x-card :title="__('app.timeline')" class="mt-4">
        <div class="space-y-4">
            @forelse($posts as $post)
                <x-post :post="$post" />
            @empty
                <div class="text-center py-8">
                    <p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_posts_yet') }}</p>
                </div>
            @endforelse
        </div>
        @if(method_exists($posts, 'links'))
            <div class="mt-4">{{ $posts->links() }}</div>
        @endif
    </x-card>
</div>

<script>
let blockTargetUserId = null;
const blockUserName = '{{ addslashes($profileUser->name) }}';

function openBlockModal(userId) {
    blockTargetUserId = userId;
    document.getElementById('block-modal-text').textContent = '{{ addslashes(__('app.block_user_confirm', ['name' => ''])) }}'.replace(':name', blockUserName);
    document.getElementById('block-modal').classList.remove('hidden');
}

function closeBlockModal() {
    blockTargetUserId = null;
    document.getElementById('block-modal').classList.add('hidden');
}

function confirmBlock() {
    if (!blockTargetUserId) return;
    const form = document.getElementById('block-form-' + blockTargetUserId);
    if (form) form.submit();
    closeBlockModal();
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeBlockModal();
    }
});
</script>
@endsection