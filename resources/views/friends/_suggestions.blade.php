@foreach($users as $person)
    <div class="flex items-center justify-between gap-3 py-3">
        <a href="{{ route('profile.show', $person) }}" class="flex items-center gap-3 min-w-0">
            @if($person->avatarUrl())
                <img src="{{ $person->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($person->name, 0, 1) }}</span>
            @endif
            <span class="font-medium truncate">{{ $person->name }}</span>
        </a>
        <form method="POST" action="{{ route('friends.send', $person) }}">@csrf
            <button type="submit" class="rounded-lg bg-fb-blue px-3 py-1.5 text-sm font-medium text-white hover:bg-fb-blue-hover">{{ __('app.add_friend') }}</button>
        </form>
    </div>
@endforeach
