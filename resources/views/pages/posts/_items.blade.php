@forelse($posts as $post)
    <x-post-group-page :post="$post" :parent-model="$page" />
@empty
@endforelse
