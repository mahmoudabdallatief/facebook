@props(['item', 'url'])

<div class="px-4 py-3">
    <a href="{{ $url }}" class="flex items-center gap-3 min-w-0">
        @if($item->coverUrl())
            <img src="{{ $item->coverUrl() }}" alt="" class="h-12 w-12 rounded-lg object-cover">
        @else
            <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-fb-blue/80 to-blue-800 dark:from-blue-950 dark:to-zinc-800 flex items-center justify-center">
                <span class="text-sm font-bold text-white">{{ mb_substr($item->name, 0, 1) }}</span>
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <span class="block font-medium text-fb-text dark:text-zinc-100 hover:underline line-clamp-1">{{ $item->name }}</span>
            <span class="block text-xs text-fb-muted dark:text-zinc-500">{{ $item->getPrivacyLabel($item->privacy) }}</span>
        </div>
    </a>
</div>
