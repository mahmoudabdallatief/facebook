@extends('layouts.app')

@section('title', __('app.posts'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <h1 class="text-2xl font-bold">{{ __('app.timeline') }}</h1>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="{{ route('posts.create') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-fb-blue px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.write_post') }}</a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse($posts as $post)
            <x-post :post="$post" />
        @empty
            <div class="text-center py-12">
                <p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_posts_yet') }}</p>
            </div>
        @endforelse
    </div>

    {{ $posts->links() }}
</div>

<script>
function toggleMenu(id) {
    const menu = document.getElementById(id);
    if (menu) {
        menu.classList.toggle('hidden');
    }
}
document.addEventListener('click', (e) => {
    if (!e.target.closest('[id$="-menu"], [id^="post-"]')) {
        document.querySelectorAll('[id$="-menu"]').forEach(m => m.classList.add('hidden'));
    }
});
</script>
@endsection