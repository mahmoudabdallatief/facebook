@props(['reactionable', 'userReaction', 'counts', 'total', 'url'])

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
@endphp

<div class="flex items-center gap-4 px-4 py-3 border-t border-fb-border dark:border-zinc-800" id="reactions-{{ $reactionable->id }}">
    <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.reactions') }}</span>

    @php($reactors = $reactionable->reactions()->with('user')->get())

    <div class="flex items-center gap-2 px-1 py-1">
        @foreach($reactionTypes as $type)
            <button
                type="button"
                onclick="toggleReaction('{{ $url }}', '{{ $type }}')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-all
                    @if($userReaction === $type)
                        bg-fb-blue/10 text-fb-blue border border-fb-blue/30
                    @else
                        bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700
                    @endif
                "
                id="reaction-btn-{{ $reactionable->id }}-{{ $type }}"
            >
                <span>{{ $icons[$type] }}</span>
                <span class="hidden sm:inline">{{ __('app.reaction_' . $type) }}</span>
                <span class="count hidden sm:inline text-xs">{{ $counts[$type] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    <span class="text-xs text-fb-muted dark:text-zinc-500 font-medium ml-auto">{{ $total }} {{ $total === 1 ? __('app.reaction') : __('app.reactions') }}</span>
    @if($reactors->isNotEmpty())
        <div class="w-full px-4 pb-2 text-xs text-fb-muted dark:text-zinc-500">
            @foreach($reactors as $r)
                <div>{{ $r->user->name }} — {{ __('app.reaction_' . $r->reaction_type) }}</div>
            @endforeach
        </div>
    @endif
</div>
