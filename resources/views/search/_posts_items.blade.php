<div class="divide-y divide-fb-border dark:divide-zinc-800">
    @foreach($posts as $post)
        <x-post-group-page :post="$post" />
    @endforeach
</div>
