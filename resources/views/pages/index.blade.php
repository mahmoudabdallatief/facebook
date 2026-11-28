@extends('layouts.app')

@section('title', __('app.pages'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-fb-text dark:text-zinc-100">{{ __('app.pages') }}</h1>
            <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.pages_subtitle') }}</p>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="{{ route('pages.create') }}" class="inline-flex w-full lg:w-auto items-center justify-center rounded-xl bg-fb-blue px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                {{ __('app.create_page') }}
            </a>
        </div>
    </div>

    <div id="pages-list-container" data-infinite-container="#pages-list-container" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @if($pages->isNotEmpty())
            @include('pages._items', ['pages' => $pages])
        @else
            <div class="col-span-full text-center py-20">
                <div class="flex flex-col items-center justify-center text-fb-muted dark:text-zinc-500">
                    <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <h3 class="text-lg font-semibold text-fb-text dark:text-zinc-100">{{ __('app.no_pages_yet') }}</h3>
                    <p class="text-sm mt-1 max-w-xs">{{ __('app.no_pages_yet_sub') }}</p>
                </div>
            </div>
        @endif
    </div>

    @if($pages->hasMorePages())
        <div id="pages-list-sentinel" data-infinite-sentinel data-url="{{ route('pages.infinite') }}" data-next-page="{{ $pages->currentPage() + 1 }}" data-has-more="true" data-container="#pages-list-container" data-load-more-btn="#pages-list-load-more-btn" class="h-4"></div>
        <div class="flex justify-center pt-4 pb-8">
            <button id="pages-list-load-more-btn" type="button" onclick="window.__infiniteLoaders['pages-list-sentinel']?.()" class="inline-flex items-center gap-2 rounded-xl bg-fb-blue px-5 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover transition-colors">
                {{ __('app.load_more') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </button>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.no_more_pages') }}</p>
        </div>
    @endif
</div>
@endsection
