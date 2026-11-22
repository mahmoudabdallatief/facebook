@php($isAr = app()->getLocale() === 'ar')
<nav class="fixed inset-x-0 top-0 z-40 border-b border-fb-border bg-fb-surface/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-2">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/facebook-logo.svg') }}" alt="{{ __('app.name') }}" class="h-9 w-9" width="36" height="36">
            <span class="text-2xl font-bold tracking-tight text-fb-blue">{{ __('app.name') }}</span>
        </a>

        <div class="flex items-center gap-2 text-sm">
@auth
                <a href="{{ route('posts.index') }}" @class([
                    'rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800',
                    'text-fb-blue bg-fb-blue/10 dark:text-blue-400 dark:bg-fb-blue/20' => request()->routeIs('posts.*'),
                    'text-fb-muted dark:text-zinc-300' => !request()->routeIs('posts.*'),
                ]) @if(request()->routeIs('posts.*')) aria-current="page" @endif>{{ __('app.posts') }}</a>
                <a href="{{ route('pages.index') }}" @class([
                    'rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800',
                    'text-fb-blue bg-fb-blue/10 dark:text-blue-400 dark:bg-fb-blue/20' => request()->routeIs('pages.*'),
                    'text-fb-muted dark:text-zinc-300' => !request()->routeIs('pages.*'),
                ]) @if(request()->routeIs('pages.*')) aria-current="page" @endif>{{ __('app.pages') }}</a>
                <a href="{{ route('groups.index') }}" @class([
                    'rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800',
                    'text-fb-blue bg-fb-blue/10 dark:text-blue-400 dark:bg-fb-blue/20' => request()->routeIs('groups.*'),
                    'text-fb-muted dark:text-zinc-300' => !request()->routeIs('groups.*'),
                ]) @if(request()->routeIs('groups.*')) aria-current="page" @endif>{{ __('app.groups') }}</a>
                <a href="{{ route('messenger.index') }}" @class([
                    'relative rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800',
                    'text-fb-blue bg-fb-blue/10 dark:text-blue-400 dark:bg-fb-blue/20' => request()->routeIs('messenger.*'),
                    'text-fb-muted dark:text-zinc-300' => !request()->routeIs('messenger.*'),
                ]) @if(request()->routeIs('messenger.*')) aria-current="page" @endif>{{ __('messenger.chats') }}
                    <span id="unread-badge" class="absolute -top-1 -right-1 hidden w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-medium flex items-center justify-center"></span>
                </a>
                <a href="{{ route('friends.index') }}" @class([
                    'rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800',
                    'text-fb-blue bg-fb-blue/10 dark:text-blue-400 dark:bg-fb-blue/20' => request()->routeIs('friends.*'),
                    'text-fb-muted dark:text-zinc-300' => !request()->routeIs('friends.*'),
                ]) @if(request()->routeIs('friends.*')) aria-current="page" @endif>{{ __('app.friends') }}</a>
                <a href="{{ route('notifications.index') }}" @class([
                    'relative rounded-lg px-3 py-1.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800',
                    'text-fb-blue bg-fb-blue/10 dark:text-blue-400 dark:bg-fb-blue/20' => request()->routeIs('notifications.*'),
                    'text-fb-muted dark:text-zinc-300' => !request()->routeIs('notifications.*'),
                ]) @if(request()->routeIs('notifications.*')) aria-current="page" @endif>{{ __('app.notifications') }}
                    <span id="notif-badge" class="absolute -top-1 -right-1 hidden w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"><span id="notif-count">0</span></span>
                </a>
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

<script>
(function(){
    var badge = document.getElementById('notif-badge');
    var countEl = document.getElementById('notif-count');
    if(!badge || !countEl) return;
    function update(){
        fetch('{{ route('notifications.unread-count') }}')
            .then(function(r){ return r.json(); })
            .then(function(data){
                var c = parseInt(data.count || 0, 10);
                countEl.textContent = c > 9 ? '9+' : c;
                badge.classList.toggle('hidden', c <= 0);
            });
    }
    update();
    setInterval(update, 30000);
})();
</script>
