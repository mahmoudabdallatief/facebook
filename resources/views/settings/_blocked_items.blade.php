@forelse($blockedUsers as $blocked)
    <div class="blocked-item flex items-center justify-between gap-3 py-2.5 px-2 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer"
         data-name="{{ $blocked->name }}"
         data-id="{{ $blocked->id }}">
        <div class="flex items-center gap-3 w-full min-w-0">
            <input type="radio" name="blocked_user_id" value="{{ $blocked->id }}"
                class="mt-0.5 shrink-0 text-fb-blue ring-offset-white focus:ring-fb-blue dark:ring-offset-zinc-900">
            @if($blocked->avatarUrl())
                <img src="{{ $blocked->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($blocked->name, 0, 1) }}</span>
            @endif
            <span class="font-medium truncate block-list-name">{{ $blocked->name }}</span>
        </div>
    </div>
@empty
@endforelse
