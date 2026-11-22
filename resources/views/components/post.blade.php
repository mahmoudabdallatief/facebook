@props(['post'])

@php($me = auth()->user())
@php($canEdit = $me && $me->id === $post->user_id)
@php($shareTarget = $post->rootOriginalPost())
@php($shareCount = $shareTarget->shareCount())
@php($alreadyShared = $me ? $shareTarget->hasSharedBy($me) : false)
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
            @if($post->isGroupPost() && $post->group)
                @php($role = $post->group->memberRole($post->user))
                @if($role)
                    <span class="text-xs text-fb-blue">{{ $role === 'admin' ? __('app.role_admin') : ($role === 'moderator' ? __('app.role_moderator') : __('app.role_member')) }}</span>
                @endif
            @endif
            <div class="text-xs text-fb-muted dark:text-zinc-400">{{ $post->created_at->diffForHumans() }}@if($post->visibility === 'friends') · {{ __('app.visibility_friends') }}@endif</div>
            @if($post->isShare())
                <div class="text-xs text-fb-muted dark:text-zinc-400">
                    @if($shareTarget->user && $shareTarget->user->id !== $post->user->id)
                        <a href="{{ route('profile.show', $shareTarget->user) }}" class="hover:underline">{{ __('app.shared_post_of', ['name' => $shareTarget->user->name]) }}</a>
                    @else
                        {{ __('app.shared_a_post') }}
                    @endif
                </div>
            @endif
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

    @if($post->body)
        <div class="px-4 pb-3 text-sm leading-relaxed">{{ nl2br(e($post->body)) }}</div>
    @endif

    @if($post->isShare())
        @include('components.shared-original', ['original' => $shareTarget, 'me' => $me])
    @elseif($post->media->isNotEmpty())
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

    <x-reactions
        :reactionable="$post"
        :user-reaction="$post->userReaction($me)"
        :counts="$post->reactionCounts()"
        :total="$post->totalReactions()"
        :url="route('posts.reactions.store', $post)"
    />

    <div class="border-t border-fb-border dark:border-zinc-800">
        <div class="p-4 flex flex-wrap gap-2">
            <button
                type="button"
                onclick="toggleCommentForm({{ $post->id }})"
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-blue hover:bg-fb-blue/5 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                <span>💬</span>
                {{ __('app.comment') }}
            </button>

            <button
                type="button"
                onclick="openShareModal({{ $post->id }})"
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-blue hover:bg-fb-blue/5 dark:text-zinc-300 dark:hover:bg-zinc-800"
            >
                <span>↗</span>
                {{ __('app.share') }}
            </button>

            <a
                href="{{ route('posts.shares.index', $shareTarget) }}"
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-muted hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
            >
                <span>🔁</span>
                {{ $shareCount }} {{ $shareCount === 1 ? __('app.share') : __('app.shares') }}
            </a>

            @if($alreadyShared)
                <span class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-muted dark:text-zinc-500">
                    <span>✓</span>
                    {{ __('app.shared') }}
                </span>
            @endif
        </div>

        <div id="comment-form-wrapper-{{ $post->id }}" class="hidden p-4 border-t border-fb-border dark:border-zinc-800">
            <form id="comment-form-{{ $post->id }}" class="flex gap-2">
                <textarea name="body" rows="1" placeholder="{{ __('app.add_comment') }}" class="flex-1 min-h-[36px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue"></textarea>
                <button
                    type="submit"
                    onclick="submitCommentForm(event, {{ $post->id }})"
                    class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover"
                >
                    {{ __('app.comment') }}
                </button>
            </form>
        </div>

        <div id="comments-list-{{ $post->id }}" class="p-4 border-t border-fb-border dark:border-zinc-800">
            @forelse($post->comments()->with('user')->get() as $comment)
                <x-comment
                    :comment="$comment"
                    :user-reaction="$comment->userReaction($me)"
                    :can-edit="$me && $me->id === $comment->user_id"
                    :can-delete="$me && $me->id === $comment->user_id"
                    :depth="0"
                />
            @empty
                <p class="text-xs text-center text-fb-muted dark:text-zinc-500 py-4">{{ __('app.no_comments_yet') }}</p>
            @endforelse
        </div>
    </div>
</article>

<!-- Share Modal -->
<div id="share-modal-{{ $post->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-fb-surface rounded-xl shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700">
            <h3 class="font-semibold">{{ __('app.share_post') }}</h3>
        </div>
        <form method="POST" action="{{ route('posts.shares.store', $shareTarget) }}" class="p-4 space-y-3">
            @csrf
            <div class="flex gap-3">
                @if($me && $me->avatarUrl())
                    <img src="{{ $me->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white">{{ $me ? mb_substr($me->name, 0, 1) : '?' }}</span>
                @endif
                <div class="min-w-0 flex-1 space-y-2">
                    <textarea name="body" rows="3" placeholder="{{ __('app.share_placeholder') }}" class="w-full rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue">{{ old('body') }}</textarea>
                    <select name="visibility" class="w-full rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue">
                        <option value="public">{{ __('app.visibility_public') }}</option>
                        <option value="friends">{{ __('app.visibility_friends') }}</option>
                    </select>
                </div>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeShareModal({{ $post->id }})" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600">{{ __('app.cancel') }}</button>
                <button type="submit" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.share') }}</button>
            </div>
        </form>
    </div>
</div>

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

function toggleCommentForm(postId) {
    const wrapper = document.getElementById('comment-form-wrapper-' + postId);
    if (wrapper) wrapper.classList.toggle('hidden');
}

function openShareModal(postId) {
    const modal = document.getElementById('share-modal-' + postId);
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeShareModal(postId) {
    const modal = document.getElementById('share-modal-' + postId);
    if (modal) {
        modal.classList.add('hidden');
    }
}

function submitCommentForm(event, postId) {
    event.preventDefault();
    console.log('submitCommentForm called', postId);
    const form = event.target.closest('form');
    console.log('form', form);
    const body = form.querySelector('textarea[name="body"]').value.trim();
    console.log('body', body);
    if (!body) return;

    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '{{ __('app.saving') }}';

    fetch('{{ route('comments.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            post_id: postId,
            body: body,
        }),
    }).then(async (res) => {
        console.log('response', res);
        if (res.ok) {
            const data = await res.json();
            console.log('data', data);
            location.reload();
        } else {
            const errorData = await res.json();
            console.error('error', errorData);
            btn.disabled = false;
            btn.textContent = '{{ __('app.comment') }}';
        }
    }).catch(err => {
        console.error('fetch error', err);
        btn.disabled = false;
        btn.textContent = '{{ __('app.comment') }}';
    });
}

function toggleReaction(url, reactionType) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
        body: 'reaction_type=' + encodeURIComponent(reactionType),
    }).then(async (res) => {
        if (res.ok) {
            const data = await res.json();
            updateReactionUI(data);
        }
    });
}

function updateReactionUI(data) {
    // Reload to reflect changes
    location.reload();
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
        document.querySelectorAll('[id^="share-modal-"]').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('[id$="-menu"]').forEach(m => m.classList.add('hidden'));
    }
});
</script>
