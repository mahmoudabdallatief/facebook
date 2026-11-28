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

    <div id="posts-container" data-infinite-container="#posts-container" class="space-y-4">
        @if($posts->isNotEmpty())
            @include('posts._items', ['posts' => $posts])
        @else
            <div class="text-center py-12">
                <p class="text-fb-muted dark:text-zinc-500">{{ __('app.no_posts_yet') }}</p>
            </div>
        @endif
    </div>

    @if($posts->hasMorePages())
        <div id="posts-sentinel" data-infinite-sentinel data-url="{{ route('posts.infinite') }}" data-next-page="{{ $posts->currentPage() + 1 }}" data-has-more="true" data-container="#posts-container" data-load-more-btn="#posts-load-more-btn" class="h-4"></div>
        <div class="flex justify-center">
            <button id="posts-load-more-btn" type="button" onclick="window.__infiniteLoaders['posts-sentinel']?.()" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.load_more') }}</button>
        </div>
    @else
        <div class="text-center py-4 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.no_more_posts') }}</div>
    @endif
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