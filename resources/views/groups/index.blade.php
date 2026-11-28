@extends('layouts.app')

@section('title', __('app.groups'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-fb-text dark:text-zinc-100">{{ __('app.groups') }}</h1>
            <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.groups_subtitle') }}</p>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
            <a href="{{ route('groups.create') }}" class="inline-flex w-full lg:w-auto items-center justify-center rounded-xl bg-fb-blue px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                {{ __('app.create_group') }}
            </a>
        </div>
    </div>

    <div id="groups-list-container" data-infinite-container="#groups-list-container" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @if($groups->isNotEmpty())
            @include('groups._items', ['groups' => $groups])
        @else
            <div class="col-span-full text-center py-20">
                <div class="flex flex-col items-center justify-center text-fb-muted dark:text-zinc-500">
                    <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h3 class="text-lg font-semibold text-fb-text dark:text-zinc-100">{{ __('app.no_groups_yet') }}</h3>
                    <p class="text-sm mt-1 max-w-xs">{{ __('app.no_groups_yet_sub') }}</p>
                </div>
            </div>
        @endif
    </div>

    @if($groups->hasMorePages())
        <div id="groups-list-sentinel" data-infinite-sentinel data-url="{{ route('groups.infinite') }}" data-next-page="{{ $groups->currentPage() + 1 }}" data-has-more="true" data-container="#groups-list-container" data-load-more-btn="#groups-list-load-more-btn" class="h-4"></div>
        <div class="flex justify-center pt-4 pb-8">
            <button id="groups-list-load-more-btn" type="button" onclick="window.__infiniteLoaders['groups-list-sentinel']?.()" class="inline-flex items-center gap-2 rounded-xl bg-fb-blue px-5 py-2.5 font-semibold text-white shadow-sm hover:bg-fb-blue-hover transition-colors">
                {{ __('app.load_more') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </button>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.no_more_groups') }}</p>
        </div>
    @endif
</div>
@endsection
