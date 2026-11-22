@props(['post', 'parentModel' => null])

@php
$me = auth()->user();
$canEdit = $me && $me->id === $post->user_id;
$canManage = false;

if ($post->isGroupPost() && $post->group) {
    $canManage = $post->group->canManage($me);
} elseif ($post->isPagePost() && $post->page) {
    $canManage = $post->page->canManage($me);
}

$canDelete = $canEdit || ($post->isPagePost() && $canManage);
$shareTarget = $post->rootOriginalPost();
$shareCount = $shareTarget->shareCount();
$shareAction = $parentModel instanceof \App\Models\Group
    ? route('groups.posts.shares.store', ['group' => $parentModel, 'post' => $shareTarget])
    : route('posts.shares.store', $shareTarget);

// Determine parent route info
if ($post->isGroupPost() && $post->group) {
    $parentRoute = 'groups.show';
    $parentName = $post->group->name;
    $parentUrl = route('groups.show', $post->group);
    $postsRoute = 'groups.posts.show';
    $postEditUrl = route('groups.posts.edit', ['group' => $post->group, 'post' => $post]);
    $postDeleteUrl = route('groups.posts.destroy', ['group' => $post->group, 'post' => $post]);
    $typeLabel = __('app.group');
} elseif ($post->isPagePost() && $post->page) {
    $parentRoute = 'pages.show';
    $parentName = $post->page->name;
    $parentUrl = route('pages.show', $post->page);
    $postsRoute = 'pages.posts.show';
    $postEditUrl = route('pages.posts.edit', ['page' => $post->page, 'post' => $post]);
    $postDeleteUrl = route('pages.posts.destroy', ['page' => $post->page, 'post' => $post]);
    $typeLabel = $post->page->type === 'group' ? __('app.group') : __('app.page');
} else {
    $parentRoute = 'posts.show';
    $parentName = null;
    $parentUrl = null;
    $postsRoute = 'posts.show';
    $postEditUrl = route('posts.edit', $post);
    $postDeleteUrl = route('posts.destroy', $post);
    $typeLabel = null;
}
@endphp

<article id="post-{{ $post->id }}" class="rounded-xl border border-fb-border bg-fb-surface shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-start gap-3 p-4">
        @if($post->isPagePost() && $post->page)
        <a href="{{ route('pages.show', $post->page) }}">
            @if($post->page->avatarUrl())
                <img src="{{ $post->page->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white">{{ mb_substr($post->page->name, 0, 1) }}</span>
            @endif
        </a>
        @else
        <a href="{{ route('profile.show', $post->user) }}">
            @if($post->user->avatarUrl())
                <img src="{{ $post->user->avatarUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
            @else
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-fb-blue text-sm font-bold text-white">{{ mb_substr($post->user->name, 0, 1) }}</span>
            @endif
        </a>
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex items-baseline gap-2 mb-1">
                @if($post->isPagePost() && $post->page)
                    <a href="{{ route('pages.show', $post->page) }}" class="font-semibold hover:underline">{{ $post->page->name }}</a>
                @else
                    <a href="{{ route('profile.show', $post->user) }}" class="font-semibold hover:underline">{{ $post->user->name }}</a>
                @endif

                @if($post->isGroupPost() && $post->group)
                    @php($role = $post->group->memberRole($post->user))
                    @if($role)
                        <span class="text-xs text-fb-blue">{{ $post->group->getRoleLabel($role) }}</span>
                    @endif
                @endif

                @if($parentName)
                    <span class="text-xs text-fb-muted dark:text-zinc-400">·</span>
                    <a href="{{ $parentUrl }}" class="text-xs text-fb-blue hover:underline font-medium">{{ $parentName }}</a>
                    <span class="text-xs text-fb-muted dark:text-zinc-400">{{ $typeLabel }}</span>
                @endif
            </div>
            <div class="text-xs text-fb-muted dark:text-zinc-400 flex items-center gap-1.5">
                <span>{{ $post->created_at->diffForHumans() }}</span>
                @if($post->isPagePost() && $post->page)
                    <span>·</span>
                    <span>{{ __('app.page_posted_by', ['name' => $post->user->name]) }}</span>
                @endif
                @if($post->visibility === 'friends')
                    <span>·</span>
                    <span>{{ __('app.visibility_friends') }}</span>
                @endif
            </div>
            @if($post->isShare())
                <div class="text-xs text-fb-muted dark:text-zinc-400 mt-1">
                    @if($shareTarget->user && $shareTarget->user->id !== $post->user->id)
                        <a href="{{ route('profile.show', $shareTarget->user) }}" class="hover:underline">{{ __('app.shared_post_of', ['name' => $shareTarget->user->name]) }}</a>
                    @else
                        {{ __('app.shared_a_post') }}
                    @endif
                </div>
            @endif
        </div>
        @if($canEdit || ($post->isPagePost() && $canManage))
            <div class="relative flex-shrink-0">
                <button type="button" onclick="toggleMenu('post-{{ $post->id }}-menu')" class="rounded-lg p-1 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                </button>
                <div id="post-{{ $post->id }}-menu" class="hidden absolute right-0 z-20 mt-1 w-48 rounded-lg border border-fb-border bg-fb-surface py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <a href="{{ $postEditUrl }}" class="block px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('app.edit_post') }}</a>
                    <button type="button" onclick="openDeleteModal('{{ $post->id }}')" class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">{{ __('app.delete_post') }}</button>
                </div>
            </div>
        @endif
    </div>

    @if($post->body)
        <div class="px-4 pb-3 text-sm leading-relaxed whitespace-pre-line">{{ nl2br(e($post->body)) }}</div>
    @endif

    @if($post->isShare())
        @include('components.shared-original', ['original' => $shareTarget, 'me' => $me])
    @elseif($post->media->isNotEmpty())
        <div class="grid gap-1 {{ $post->media->count() === 1 ? 'grid-cols-1' : ($post->media->count() === 2 ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3') }}">
            @foreach($post->media as $media)
                @if($media->type === 'video')
                    <video src="{{ $media->url }}" controls class="h-64 w-full object-cover rounded-t-xl"></video>
                @else
                    <img src="{{ $media->url }}" alt="" class="h-64 w-full object-cover rounded-t-xl">
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
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-blue hover:bg-fb-blue/5 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors"
            >
                <span>💬</span>
                {{ __('app.comment') }}
            </button>

            <button
                type="button"
                onclick="openShareModal({{ $post->id }})"
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-blue hover:bg-fb-blue/5 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors"
            >
                <span>↗</span>
                {{ __('app.share') }}
            </button>

            <a
                href="{{ route('posts.shares.index', $shareTarget) }}"
                class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-fb-muted hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 transition-colors"
            >
                <span>🔁</span>
                {{ $shareCount }} {{ $shareCount === 1 ? __('app.share') : __('app.shares') }}
            </a>
        </div>

        <div id="comment-form-wrapper-{{ $post->id }}" class="hidden p-4 border-t border-fb-border dark:border-zinc-800">
            <form id="comment-form-{{ $post->id }}" action="{{ route('comments.store') }}" method="POST" class="flex gap-2" onsubmit="submitCommentForm(event, {{ $post->id }})">
                @csrf
                <textarea name="body" rows="1" placeholder="{{ __('app.add_comment') }}" class="flex-1 min-h-[36px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue resize-none" style="height: 36px;"></textarea>
                <button
                    type="submit"
                    class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover transition-colors flex-shrink-0"
                >
                    {{ __('app.comment') }}
                </button>
            </form>
        </div>

        <div id="comments-list-{{ $post->id }}" class="p-4 border-t border-fb-border dark:border-zinc-800">
            <div id="comments-container-{{ $post->id }}">
                @include('components.comments-tree', ['post' => $post, 'comments' => $post->comments()->with(['user', 'reactions', 'children.user', 'children.reactions'])->get()])
            </div>
        </div>
    </div>
</article>

<!-- Share Modal -->
<div id="share-modal-{{ $post->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
    <div class="bg-fb-surface rounded-xl shadow-xl max-w-md w-full mx-4 dark:bg-zinc-900">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700">
            <h3 class="font-semibold">{{ __('app.share_post') }}</h3>
        </div>
        <form method="POST" action="{{ $shareAction }}" class="p-4 space-y-3">
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
            <form method="POST" action="{{ $postDeleteUrl }}">
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
            body: body,
        }),
    }).then(async (res) => {
        if (res.ok) {
            const data = await res.json();
            if (data.success && data.comment) {
                appendComment(postId, data.comment);
                form.querySelector('textarea[name="body"]').value = '';
            }
            location.reload();
        } else {
            const errorData = await res.json();
            btn.disabled = false;
            btn.textContent = '{{ __('app.comment') }}';
            showToast(errorData.message || '{{ __('app.something_went_wrong') }}', 'error');
        }
    }).catch(err => {
        btn.disabled = false;
        btn.textContent = '{{ __('app.comment') }}';
        showToast('{{ __('app.something_went_wrong') }}', 'error');
    });
}

function appendComment(postId, comment) {
    const container = document.getElementById('comments-container-' + postId);
    if (!container) return;

    const emptyMsg = container.querySelector('p.text-center');
    if (emptyMsg) emptyMsg.remove();

    const commentEl = createCommentElement(comment);
    container.insertBefore(commentEl, container.firstChild);
}

function createCommentElement(comment, depth = 0) {
    const div = document.createElement('div');
    div.id = 'comment-' + comment.id;
    div.className = 'border-b border-fb-border dark:border-zinc-800 pt-3 pb-4' + (depth > 0 ? ' pl-12 border-l-2 border-l-zinc-200 dark:border-l-zinc-800' : '');

    const me = {{ json_encode(auth()->user() ? ['id' => auth()->user()->id] : null) }};
    const canEdit = me && me.id === comment.user.id;
    const canDelete = canEdit;

    const reactionIcons = {
        'like': '👍', 'love': '❤️', 'haha': '😂', 'wow': '😮', 'sad': '😢', 'angry': '😡'
    };

    const reactionLabels = {
        'like': @json(__('app.reaction_like')),
        'love': @json(__('app.reaction_love')),
        'haha': @json(__('app.reaction_haha')),
        'wow': @json(__('app.reaction_wow')),
        'sad': @json(__('app.reaction_sad')),
        'angry': @json(__('app.reaction_angry'))
    };

    const commentReactionUrlTemplate = @json(route('comments.reactions.store', ['comment' => '__COMMENT_ID__']));
    const profileUrlTemplate = @json(route('profile.show', ['user' => '__USER_ID__']));

    let reactionsHtml = '<div class="flex items-center gap-2 px-1 py-1">';
    for (const type of Object.keys(reactionIcons)) {
        const count = comment.reaction_counts?.[type] ?? 0;
        const isUserReaction = comment.user_reaction === type;
        const reactionUrl = commentReactionUrlTemplate.replace('__COMMENT_ID__', encodeURIComponent(comment.id));
        reactionsHtml += '<button type="button" onclick="toggleReaction(\'' + reactionUrl + '\', \'' + type + '\')" class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all ' + (isUserReaction ? 'bg-fb-blue/10 text-fb-blue border border-fb-blue/30' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700') + '" id="reaction-btn-' + comment.id + '-' + type + '"><span>' + reactionIcons[type] + '</span><span class="hidden sm:inline">' + reactionLabels[type] + '</span><span class="count hidden sm:inline text-xs">' + count + '</span></button>';
    }
    reactionsHtml += '</div>';

    let childrenHtml = '';
    if (comment.children && comment.children.length > 0) {
        childrenHtml = '<div class="mt-3">';
        for (const child of comment.children) {
            childrenHtml += createCommentElement(child, depth + 1);
        }
        childrenHtml += '</div>';
    }

    div.innerHTML = `
        <div class="flex gap-3">
            <div class="flex-shrink-0">
                ${comment.user.avatar_url ? '<img src="' + comment.user.avatar_url + '" alt="" class="h-8 w-8 rounded-full object-cover">' : '<span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">' + comment.user.name.charAt(0) + '</span>'}
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-baseline gap-2 mb-1">
                    <a href="${profileUrlTemplate.replace('__USER_ID__', encodeURIComponent(comment.user.id))}" class="text-sm font-semibold text-fb-text dark:text-zinc-200 hover:underline">${comment.user.name}</a>
                    ${depth > 0 ? '<span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.reply') }}</span>' : ''}
                    <span class="text-xs text-fb-muted dark:text-zinc-500">${comment.created_at}</span>
                </div>
                <div class="text-sm text-fb-text dark:text-zinc-200 whitespace-pre-line break-words">${comment.body}</div>

                <div class="flex items-center gap-3 mt-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.reactions') }}</span>
                        ${reactionsHtml}
                    </div>

                    ${canEdit ? '<button type="button" onclick="editComment(' + comment.id + ')" class="text-xs text-fb-muted hover:text-fb-blue dark:hover:text-zinc-300">{{ __('app.edit') }}</button>' : ''}
                    ${canDelete ? '<button type="button" onclick="deleteComment(' + comment.id + ', ' + comment.post_id + ')" class="text-xs text-red-600 hover:text-red-700 dark:hover:text-red-400">{{ __('app.delete') }}</button>' : ''}

                    <button type="button" onclick="toggleReplyForm(' + comment.id + ')" class="text-xs text-fb-blue hover:text-fb-blue-hover dark:text-zinc-300 dark:hover:text-zinc-100">{{ __('app.reply') }}</button>
                </div>

                ${comment.reactors && comment.reactors.length ? '<div class="comment-reactors mt-2 flex flex-wrap gap-2"></div>' : ''}

                <!-- Reply form (hidden by default) -->
                <div id="reply-form-${comment.id}" class="hidden mt-3">
                    <form id="comment-form-${comment.id}" action="{{ route('comments.store') }}" method="POST" class="flex gap-2" onsubmit="submitReplyForm(event, ${comment.id}, ${comment.post_id})">
                        @csrf
                        <textarea name="body" rows="1" placeholder="{{ __('app.write_reply') }}" class="flex-1 min-h-[32px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue resize-none" style="height: 32px;"></textarea>
                        <button type="submit" class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.reply') }}</button>
                    </form>
                </div>

                ${childrenHtml}
            </div>
        </div>
    `;

    const reactorsContainer = div.querySelector('.comment-reactors');
    if (reactorsContainer) {
        for (const reactor of comment.reactors) {
            const reactorTag = document.createElement('span');
            reactorTag.className = 'rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
            reactorTag.textContent = `${reactor.name} · ${reactionLabels[reactor.reaction_type] || reactor.reaction_type}`;
            reactorsContainer.appendChild(reactorTag);
        }
    }

    return div;
}

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

    const updateUrl = '{{ route('comments.update', ['comment' => '__COMMENT_ID__']) }}'.replace('__COMMENT_ID__', encodeURIComponent(commentId));

    fetch(updateUrl, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ body: body }),
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
    const deleteUrl = '{{ route('comments.destroy', ['comment' => '__COMMENT_ID__']) }}'.replace('__COMMENT_ID__', encodeURIComponent(commentId));

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

        const list = document.getElementById('comments-container-' + postId);
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
            location.reload();
        }
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
        document.querySelectorAll('[id^="delete-comment-modal-"]').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('[id^="share-modal-"]').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('[id$="-menu"]').forEach(m => m.classList.add('hidden'));
    }
});
</script>
