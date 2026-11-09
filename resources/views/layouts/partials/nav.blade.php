@php($isAr = app()->getLocale() === 'ar')
<nav class="sticky top-0 z-40 border-b border-fb-border bg-fb-surface/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-2">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/facebook-logo.svg') }}" alt="{{ __('app.name') }}" class="h-9 w-9" width="36" height="36">
            <span class="text-2xl font-bold tracking-tight text-fb-blue">{{ __('app.name') }}</span>
        </a>

        <div class="flex items-center gap-2 text-sm">
            @auth
                <a href="{{ route('friends.index') }}" class="rounded-lg px-3 py-1.5 font-medium text-fb-muted hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('app.friends') }}</a>
            @endauth
            <a href="{{ route('locale.switch', $isAr ? 'en' : 'ar') }}" class="rounded-lg px-3 py-1.5 text-fb-muted hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ $isAr ? 'EN' : 'ع' }}</a>
            <button type="button" onclick="toggleTheme()" class="rounded-lg px-3 py-1.5 text-fb-muted hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" aria-label="{{ __('app.toggle_theme') }}">◐</button>

            @auth
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-lg px-2 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">
                    @if(auth()->user()->avatarUrl())
                        <img src="{{ auth()->user()->avatarUrl() }}" alt="" class="h-7 w-7 rounded-full object-cover">
                    @else
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    @endif
                    <span class="max-w-[10rem] truncate">{{ auth()->user()->name }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-zinc-200 px-3 py-1.5 font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('app.login') }}</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-fb-blue px-3 py-1.5 font-medium text-white hover:bg-fb-blue-hover">{{ __('app.register') }}</a>
            @endauth
        </div>
    </div>
</nav>