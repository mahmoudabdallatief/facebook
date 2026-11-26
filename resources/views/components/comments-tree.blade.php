@props(['post', 'comments' => []])

@php
$me = auth()->user();
$reactionTypes = \App\Models\Reaction::REACTION_TYPES;
$icons = [
    'like' => '👍',
    'love' => '❤️',
    'haha' => '😂',
    'wow' => '😮',
    'sad' => '😢',
    'angry' => '😡',
];
@endphp

@if(empty($comments))
    <p class="text-xs text-center text-fb-muted dark:text-zinc-500 py-4">{{ __('app.no_comments_yet') }}</p>
@else
    @foreach($comments as $comment)
        @include('components.comment-tree-item', ['comment' => $comment, 'depth' => 0])
    @endforeach
@endif