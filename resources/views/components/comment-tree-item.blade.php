@props(['comment', 'depth' => 0])

@php
$me = auth()->user();
$blockedForViewer = $me && $comment->user && $me->hasBlockRelationWith($comment->user);
$canEdit = $me && $me->id === $comment->user_id;
$canDelete = $canEdit;
    $reactionCounts = $comment->reactionCounts();
    $totalReactions = $comment->total_reactions ?? $comment->totalReactions();
$userReaction = $comment->userReaction($me);
$children = $comment->children ?? collect();
@endphp

<div
    id="comment-{{ $comment->id }}"
    class="relative border-b border-fb-border/70 dark:border-zinc-800 py-4 {{ $depth > 0 ? 'ml-10 sm:ml-12 pl-3 sm:pl-4 border-l-2 border-l-zinc-200 dark:border-l-zinc-700' : '' }}"
>
    <div class="flex gap-3">
        <div class="flex-shrink-0">
            @if($comment->user->avatarUrl())
                <img src="{{ $comment->user->avatarUrl() }}" alt="" loading="lazy" decoding="async" class="h-8 w-8 rounded-full object-cover">
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

            @unless($blockedForViewer)
            <div class="mt-2">
                <x-reactions
                    :reactionable="$comment"
                    :user-reaction="$userReaction"
                    :counts="$reactionCounts"
                    :total="$totalReactions"
                    :url="route('comments.reactions.store', $comment)"
                />
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-3">

                @if($canEdit)
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
                <form id="comment-form-{{ $comment->id }}" action="{{ route('comments.store') }}" method="POST" class="flex gap-2" onsubmit="submitReplyForm(event, {{ $comment->id }}, {{ $comment->post_id }})">
                    @csrf
                    <textarea name="body" rows="1" placeholder="{{ __('app.write_reply') }}" class="flex-1 min-h-[32px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue resize-none" style="height: 32px;"></textarea>
                    <button
                        type="submit"
                        class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover"
                    >
                        {{ __('app.reply') }}
                    </button>
                </form>
            </div>
            @endunless

        </div>
    </div>

    <!-- Children replies sit below the complete parent row. -->
    @if($children->isNotEmpty())
        <div class="mt-2">
            @foreach($children as $child)
                @include('components.comment-tree-item', ['comment' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>

@if($canEdit)
    <!-- Delete confirmation modal -->
    <div id="delete-comment-modal-{{ $comment->id }}" data-delete-url="{{ route('comments.destroy', $comment) }}" data-deleted-message="{{ __('app.comment_deleted') }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
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
        <form id="edit-comment-form-{{ $comment->id }}" action="{{ route('comments.update', $comment) }}" method="POST" class="flex gap-2" onsubmit="submitEditComment(event, {{ $comment->id }})">
            @csrf @method('PATCH')
            <textarea name="body" rows="2" class="flex-1 min-h-[48px] rounded-lg border border-fb-border bg-fb-surface px-3 py-2 text-sm text-fb-text dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-fb-blue">{{ $comment->body }}</textarea>
            <button type="submit" class="rounded-lg bg-fb-blue px-3 py-2 text-xs font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.save_changes') }}</button>
            <button type="button" onclick="cancelEditComment({{ $comment->id }})" class="rounded-lg bg-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</button>
        </form>
    </div>
@endif
