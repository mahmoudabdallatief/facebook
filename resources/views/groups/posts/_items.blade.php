@forelse($posts as $post)
    <x-post-group-page :post="$post" :parent-model="$group" />
@empty
@endforelse
