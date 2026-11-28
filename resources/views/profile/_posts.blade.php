@forelse($posts as $post)
    <x-post :post="$post" />
@empty
@endforelse
