@props(['comment', 'userReaction', 'canEdit', 'canDelete', 'depth' => 0])

@php
$reactionTypes = \App\Models\Reaction::REACTION_TYPES;
$icons = [
    'like' => '👍',
    'love' => '❤️',
    'haha' => '😂',
    'wow' => '😮',
    'sad' => '😢',
    'angry' => '😡',
];
$me = auth()->user();
@endphp

<div
    id="comment-{{ $comment->id }}"
    class="border-b border-fb-border dark:border-zinc-800 pt-3 pb-4 {{ $depth > 0 ? 'pl-12 border-l-2 border-l-zinc-200 dark:border-l-zinc-800' : '' }}"
>
    <div class="flex gap-3">
        <div class="flex-shrink-0">
            @if($comment->user->avatarUrl())
                <img src="{{ $comment->user->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($comment->user->name, 0, 1) }}</span>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-baseline gap-2 mb-1">
                <a href="{{ route('profile.show', $comment->user) }}" class="text-sm font-semibold text-fb-text dark:text-zinc-200 hover:underline">{{ $comment->user->name }}</a>
                @if($depth > 0)
                    <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.reply') }}</span>
                @endif
                <span class="text-xs text-fb-muted dark:text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <div class="text-sm text-fb-text dark:text-zinc-200 whitespace-pre-line break-words">{{ nl2br(e($comment->body)) }}</div>

            <div class="flex items-center gap-3 mt-2">
                <x-reactions
                    :reactionable="$comment"
                    :user-reaction="$userReaction"
                    :counts="$comment->reactionCounts()"
                    :total="$comment->total_reactions"
                    :url="route('comments.reactions.store', $comment)"
                />

                @if($me && $me->id === $comment->user_id)
                    <button
                        type="button"
                        onclick="editComment({{ $comment->id }})"
                        class="text-xs text-fb-muted hover:text-fb-blue dark:hover:text-zinc-300"
                    >
                        {{ __('app.edit') }}
                    </button>
                    <button
                        type="button"
                        onclick="deleteComment({{ $comment->id }}, {{ $comment->post_id }})"
                        class="text-xs text-red-600 hover:text-red-700 dark:hover:text-red-400"
                    >
                        {{ __('app.delete') }}
                    </button>
                @endif

                <button
                    type="button"
                    onclick="toggleReplyForm({{ $comment->id }})"
                    class="text-xs text-fb-blue hover:text-fb-blue-hover dark:text-zinc-300 dark:hover:text-zinc-100"
                >
                    {{ __('app.reply') }}
                </button>

            </div>

            <!-- Reply form (hidden by default) -->
            <div id="reply-form-{{ $comment->id }}" class="hidden mt-3">
                <form id="comment-form-{{ $comment->id }}" class="flex gap-2">
                    <textarea name="body" rows="1" placeholder="{{ __('app.write_reply') }}" class="flex-1 min-h-[32px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue"></textarea>
                    <button
                        type="submit"
                        onclick="submitReplyForm(event, {{ $comment->id }}, {{ $comment->post_id ?? 0 }})"
                        class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover"
                    >
                        {{ __('app.reply') }}
                    </button>
                </form>
            </div>

            <!-- Children replies -->
            @if(isset($comment->children) && $comment->children->isNotEmpty())
                <div class="mt-3">
                    @foreach($comment->children as $child)
                        <x-comment :comment="$child" :user-reaction="$child->userReaction($me)" :can-edit="$me && $me->id === $child->user_id" :can-delete="$me && $me->id === $child->user_id" :depth="$depth + 1" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

@if($me && $me->id === $comment->user_id)
    <!-- Delete confirmation modal -->
    <div id="delete-comment-modal-{{ $comment->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
        <div class="bg-fb-surface rounded-xl shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
            <div class="p-4 border-b border-fb-border dark:border-zinc-700">
                <h3 class="font-semibold">{{ __('app.delete_comment') }}</h3>
            </div>
            <div class="p-4 text-sm text-fb-text dark:text-zinc-300">
                {{ __('app.delete_comment_confirm') }}
            </div>
            <div class="p-4 border-t border-fb-border dark:border-zinc-700 flex justify-end gap-2">
                <button type="button" onclick="cancelDeleteComment({{ $comment->id }})" class="rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</button>
                <button type="button" onclick="confirmDeleteComment({{ $comment->id }}, {{ $comment->post_id }})" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('app.delete') }}</button>
            </div>
        </div>
    </div>

    <!-- Edit form (hidden by default) -->
    <div id="edit-form-{{ $comment->id }}" class="hidden border-t border-fb-border dark:border-zinc-800 pt-3 pb-4 mt-2">
        <form id="edit-comment-form-{{ $comment->id }}" class="flex gap-2" onsubmit="submitEditComment(event, {{ $comment->id }})">
            @csrf @method('PATCH')
            <textarea name="body" rows="2" class="flex-1 min-h-[48px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue">{{ $comment->body }}</textarea>
            <button type="submit" class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.save_changes') }}</button>
            <button type="button" onclick="cancelEditComment({{ $comment->id }})" class="rounded-lg bg-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</button>
        </form>
    </div>
@endif

<script>
function toggleReplyForm(commentId) {
    const form = document.getElementById('reply-form-' + commentId);
    if (form) form.classList.toggle('hidden');
}

function editComment(commentId) {
    document.getElementById('comment-' + commentId)?.classList.add('hidden');
    document.getElementById('edit-form-' + commentId)?.classList.remove('hidden');
}

function cancelEditComment(commentId) {
    document.getElementById('comment-' + commentId)?.classList.remove('hidden');
    document.getElementById('edit-form-' + commentId)?.classList.add('hidden');
}

function submitEditComment(event, commentId) {
    event.preventDefault();
    const form = event.target.closest('form');
    const body = form.querySelector('textarea[name="body"]').value.trim();
    if (!body) return;

    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '{{ __('app.saving') }}';

    const updateUrl = @json(route('comments.update', ['comment' => '__COMMENT_ID__']))
        .replace('__COMMENT_ID__', encodeURIComponent(commentId));

    fetch(updateUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            body: body,
        }),
    }).then(async (res) => {
        if (res.ok) {
            location.reload();
        } else {
            const errorData = await res.json();
            showToast(errorData.message || '{{ __('app.something_went_wrong') }}', 'error');
            btn.disabled = false;
            btn.textContent = '{{ __('app.save_changes') }}';
        }
    }).catch(err => {
        showToast('{{ __('app.something_went_wrong') }}', 'error');
        btn.disabled = false;
        btn.textContent = '{{ __('app.save_changes') }}';
    });
}

function deleteComment(commentId, postId) {
    document.getElementById('delete-comment-modal-' + commentId)?.classList.remove('hidden');
    window.currentDeleteComment = { id: commentId, postId: postId };
}

function cancelDeleteComment(commentId) {
    document.getElementById('delete-comment-modal-' + commentId)?.classList.add('hidden');
}

function confirmDeleteComment(commentId, postId) {
    const deleteUrl = @json(route('comments.destroy', ['comment' => '__COMMENT_ID__']))
        .replace('__COMMENT_ID__', encodeURIComponent(commentId));

    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
    }).then(async (res) => {
        const el = document.getElementById('comment-' + commentId);
        if (el) el.remove();
        cancelDeleteComment(commentId);
        showToast('{{ __('app.comment_deleted') }}', 'success');

        const list = document.getElementById('comments-list-' + postId);
        if (list && !list.querySelector('[id^="comment-"]')) {
            list.innerHTML = '<p class="text-xs text-center text-fb-muted dark:text-zinc-500 py-4">{{ __('app.no_comments_yet') }}</p>';
        }
    }).catch(err => {
        showToast('{{ __('app.something_went_wrong') }}', 'error');
        cancelDeleteComment(commentId);
    });
}

function submitReplyForm(event, commentId, postId) {
    event.preventDefault();
    const form = event.target.closest('form');
    const body = form.querySelector('textarea[name="body"]').value.trim();
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
            parent_id: commentId,
            body: body,
        }),
    }).then(async (res) => {
        if (res.ok) {
            const data = await res.json();
            location.reload();
        } else {
            const errorData = await res.json();
            showToast(errorData.message || '{{ __('app.something_went_wrong') }}', 'error');
            btn.disabled = false;
            btn.textContent = '{{ __('app.reply') }}';
        }
    }).catch(err => {
        showToast('{{ __('app.something_went_wrong') }}', 'error');
        btn.disabled = false;
        btn.textContent = '{{ __('app.reply') }}';
    });
}

function showToast(message, type = 'error') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
    toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
