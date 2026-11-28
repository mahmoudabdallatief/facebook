@foreach($requests as $req)
    <div class="flex items-center justify-between gap-3 py-3">
        <a href="{{ route('profile.show', $req->friend) }}" class="flex items-center gap-3 min-w-0">
            @if($req->friend->avatarUrl())
                <img src="{{ $req->friend->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($req->friend->name, 0, 1) }}</span>
            @endif
            <span class="font-medium truncate">{{ $req->friend->name }}</span>
        </a>
        <form method="POST" action="{{ route('friends.cancel', $req) }}">@csrf
            <button type="submit" class="rounded-lg bg-zinc-200 px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel_request') }}</button>
        </form>
    </div>
@endforeach
