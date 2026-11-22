@extends('layouts.app')

@section('title', __('app.create_page'))

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card :title="__('app.create_page')">
        <form method="POST" action="{{ route('pages.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="space-y-1">
                <label for="name" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.page_name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                @error('name')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="slug" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.page_slug') }}</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="my-page" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                @error('slug')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.page_slug') }} /my-page</p>
            </div>

            <div class="space-y-1">
                <label for="description" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.page_description') }}</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="cover_image" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.page_cover') }}</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                </div>
                <div>
                    <label for="avatar" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.page_avatar') }}</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('pages.index') }}" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</a>
                <x-button variant="primary">{{ __('app.create_page') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
