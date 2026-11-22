@extends('layouts.app')

@section('title', ($post->isShare() ? __('app.shared_a_post') : __('app.post')) . ' · ' . $post->user->name)

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">{{ __('app.post') }}</h1>
        <a href="{{ route('posts.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
            <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            {{ __('app.back_to_timeline') }}
        </a>
    </div>

    <x-post-group-page :post="$post" />
</div>
@endsection