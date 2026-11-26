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
    $me = auth()->user();
    $blockedForViewer = $me && $reactionable->user && $me->hasBlockRelationWith($reactionable->user);
    $reactors = $reactionable->reactions()->with('user')->get();
    if ($me) {
        $reactors = $reactors->reject(function ($r) use ($me) {
            return $me->hasBlockRelationWith($r->user);
        });
    }
@endphp

<div class="flex min-w-0 w-full flex-col gap-2 py-2" id="reactions-{{ $reactionable->id }}">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
        <span class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.reactions') }}</span>

        <div class="flex min-w-0 flex-wrap items-center gap-1.5">
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

        <span class="ml-auto text-xs font-medium text-fb-muted dark:text-zinc-500">{{ $total }} {{ $total === 1 ? __('app.reaction') : __('app.reactions') }}</span>
    </div>
    @if(! $blockedForViewer && $reactors->isNotEmpty())
        <div class="flex min-w-0 flex-wrap gap-2" aria-label="{{ __('app.reactions') }}">
            @foreach($reactors as $r)
                <span class="max-w-full break-words rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $r->user->name }} · {{ __('app.reaction_' . $r->reaction_type) }}
                </span>
            @endforeach
        </div>
    @endif
</div>
