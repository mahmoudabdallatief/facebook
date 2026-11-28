@forelse($pages as $page)
    <article class="group relative block overflow-hidden rounded-2xl border border-fb-border bg-fb-surface shadow-sm transition-all hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
        <div class="relative h-32 w-full">
            @if($page->coverUrl())
                <img src="{{ $page->coverUrl() }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300 dark:bg-zinc-800">
            @else
                <div class="h-full w-full bg-gradient-to-br from-fb-blue/90 to-blue-900 dark:from-blue-950 dark:to-zinc-800"></div>
            @endif
            <div class="absolute -inset-0.5 bg-gradient-to-r from-transparent via-fb-blue/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none rounded-[1px]"></div>
        </div>

        <div class="p-4">
            <div class="flex items-start gap-3">
                <div class="-mt-8 shrink-0">
                    <div class="relative inline-block">
                        @if($page->avatarUrl())
                            <img src="{{ $page->avatarUrl() }}" alt="" class="h-14 w-14 rounded-full object-cover border-2 border-fb-surface dark:border-zinc-900 bg-fb-surface dark:bg-zinc-900 shadow-sm">
                        @else
                            <span class="flex h-14 w-14 items-center justify-center rounded-full border-2 border-fb-surface dark:border-zinc-900 bg-fb-blue text-xl font-bold text-white shadow-sm">{{ mb_substr($page->name, 0, 1) }}</span>
                        @endif
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="text-base font-semibold text-fb-text dark:text-zinc-100 truncate">{{ $page->name }}</h2>
                        @if($page->is_verified)
                            <span class="inline-flex items-center gap-0.5 rounded bg-fb-blue/10 px-1.5 py-0.5 text-[10px] text-fb-blue">{{ __('app.verified') }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-fb-muted dark:text-zinc-500 mb-1">{{ __('app.page') }}</div>
                    @if($page->description)
                        <div class="text-sm text-fb-text dark:text-zinc-200 line-clamp-2 leading-relaxed">{{ nl2br(e($page->description)) }}</div>
                    @endif
                </div>
            </div>

            <div class="mt-3 flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1 text-fb-muted dark:text-zinc-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    {{ $page->members_count }} {{ __('app.page_followers') }}
                </span>
                <span class="flex items-center gap-1 text-fb-muted dark:text-zinc-500">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2h-3m-9 4a2 2 0 11-4 0 2 2 0 014 0zm-4 8v1m4-4v1m11-4h3m4 4h3m-6-4h3m3 3h3m-3-11h3m3 11h3"></path></svg>
                    {{ $page->admins_count }} {{ __('app.admins') }}
                </span>
            </div>

            <span class="mt-3 inline-flex items-center gap-1 rounded-lg border border-fb-border px-2 py-1 text-[10px] font-medium text-fb-muted dark:border-zinc-700 dark:text-zinc-500">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                {{ __('app.page_privacy_'.$page->privacy) }}
            </span>
        </div>
    </article>
@empty
@endforelse
