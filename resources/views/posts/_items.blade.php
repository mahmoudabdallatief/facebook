<div class="space-y-4">
    @forelse($posts as $post)
        <x-post-group-page :post="$post" />
    @empty
    @endforelse
</div>
