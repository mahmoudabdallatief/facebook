<div class="divide-y divide-fb-border dark:divide-zinc-800">
    @foreach($users as $user)
        @include('search.items.user', ['user' => $user])
    @endforeach
</div>
