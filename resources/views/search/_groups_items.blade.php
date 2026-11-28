<div class="divide-y divide-fb-border dark:divide-zinc-800">
    @foreach($groups as $group)
        @include('search.items.group', ['item' => $group, 'url' => route('groups.show', $group)])
    @endforeach
</div>
