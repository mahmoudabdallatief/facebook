@foreach($requests as $req)
    <div class="flex items-center justify-between gap-3 py-3">
        <a href="{{ route('profile.show', $req->user) }}" class="flex items-center gap-3 min-w-0">
            @if($req->user->avatarUrl())
                <img src="{{ $req->user->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($req->user->name, 0, 1) }}</span>
            @endif
            <span class="font-medium truncate">{{ $req->user->name }}</span>
        </a>
        <div class="flex items-center gap-2 shrink-0">
            <form method="POST" action="{{ route('friends.accept', $req) }}">@csrf
                <button type="submit" class="rounded-lg bg-fb-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-fb-blue-hover">{{ __('app.accept') }}</button>
            </form>
            <form method="POST" action="{{ route('friends.reject', $req) }}">@csrf
                <button type="submit" class="rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.reject') }}</button>
            </form>
        </div>
    </div>
@endforeach
