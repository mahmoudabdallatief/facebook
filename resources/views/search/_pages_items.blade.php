<div class="divide-y divide-fb-border dark:divide-zinc-800">
    @foreach($pages as $page)
        @include('search.items.group', ['item' => $page, 'url' => route('pages.show', $page)])
    @endforeach
</div>
