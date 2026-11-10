@props(['post'])

@php($me = auth()->user())
@php($canEdit = $me && $me->id === $post->user_id)
<article id="post-{{ $post->id }}" class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-center gap-3 p-4">
        <a href="{{ route('profile.show', $post->user) }}">
            @if($post->user->avatarUrl())
                <img src="{{ $post->user->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white">{{ mb_substr($post->user->name, 0, 1) }}</span>
            @endif
        </a>
        <div class="min-w-0 flex-1">
            <a href="{{ route('profile.show', $post->user) }}" class="font-semibold hover:underline">{{ $post->user->name }}</a>
            <div class="text-xs text-fb-muted dark:text-zinc-400">{{ $post->created_at->diffForHumans() }}@if($post->visibility === 'friends') · {{ __('app.visibility_friends') }}@endif</div>
        </div>
        @if($canEdit)
            <div class="relative">
                <button type="button" onclick="toggleMenu('post-{{ $post->id }}-menu')" class="rounded-lg p-1 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                </button>
                <div id="post-{{ $post->id }}-menu" class="hidden absolute right-0 z-20 mt-1 w-40 rounded-lg border border-fb-border bg-fb-surface py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <a href="{{ route('posts.edit', $post) }}" class="block px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('app.edit_post') }}</a>
                    <button type="button" onclick="openDeleteModal('{{ $post->id }}')" class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">{{ __('app.delete_post') }}</button>
                </div>
            </div>
        @endif
    </div>

    <div class="px-4 pb-3 text-sm leading-relaxed">{{ nl2br(e($post->body)) }}</div>

    @if($post->media->isNotEmpty())
        <div class="grid gap-1 {{ $post->media->count() === 1 ? 'grid-cols-1' : ($post->media->count() === 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3') }}">
            @foreach($post->media as $media)
                @if($media->type === 'video')
                    <video src="{{ $media->url }}" controls class="h-64 w-full object-cover"></video>
                @else
                    <img src="{{ $media->url }}" alt="" class="h-64 w-full object-cover">
                @endif
            @endforeach
        </div>
    @endif

    <div class="flex items-center justify-between border-t border-fb-border px-4 py-2 dark:border-zinc-800">
        <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.comments') }}</span>
    </div>
</article>

<!-- Delete Confirmation Modal -->
<div id="delete-modal-{{ $post->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-fb-surface rounded-xl shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700">
            <h3 class="font-semibold">{{ __('app.delete_post') }}</h3>
        </div>
        <div class="p-4 text-sm text-fb-text dark:text-zinc-300">
            {{ __('app.delete_post_confirm') }}
        </div>
        <div class="p-4 border-t border-fb-border dark:border-zinc-700 flex justify-end gap-2">
            <button type="button" onclick="closeDeleteModal('{{ $post->id }}')" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-100">{{ __('app.cancel') }}</button>
            <form method="POST" action="{{ route('posts.destroy', $post) }}">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('app.delete_post') }}</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMenu(id) {
    const menu = document.getElementById(id);
    if (menu) menu.classList.toggle('hidden');
}

function openDeleteModal(postId) {
    document.getElementById('delete-modal-' + postId)?.classList.remove('hidden');
    document.getElementById('post-' + postId + '-menu')?.classList.add('hidden');
}

function closeDeleteModal(postId) {
    document.getElementById('delete-modal-' + postId)?.classList.add('hidden');
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('[id$="-menu"], [id^="post-"]')) {
        document.querySelectorAll('[id$="-menu"]').forEach(m => m.classList.add('hidden'));
    }
    if (e.target.classList.contains('fixed')) {
        e.target.classList.add('hidden');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="delete-modal-"]').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('[id$="-menu"]').forEach(m => m.classList.add('hidden'));
    }
});
</script>