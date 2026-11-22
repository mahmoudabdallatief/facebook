@extends('layouts.app')

@section('title', __('app.write_post_in_group', ['name' => $group->name]))

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card :title="__('app.write_post_in_group', ['name' => $group->name])">
        <div class="mb-4 p-3 rounded-lg border border-fb-border bg-fb-bg/50 text-sm text-fb-text dark:border-zinc-800 dark:bg-zinc-950/40">
            <span class="font-semibold">{{ $group->name }}</span>
            <span class="ml-2 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.group') }}</span>
        </div>

        <form method="POST" action="{{ route('groups.posts.store', $group) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="space-y-1">
                <label for="body" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.post_placeholder') }}</label>
                <textarea
                    id="body"
                    name="body"
                    rows="6"
                    class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900"
                    placeholder="{{ __('app.post_placeholder') }}"
                    required autofocus>{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.media_hint') }}</p>
            </div>

            <div class="space-y-1">
                <label for="visibility" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.visibility') }}</label>
                <select id="visibility" name="visibility" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="public">{{ __('app.visibility_public') }}</option>
                    <option value="friends">{{ __('app.visibility_friends') }}</option>
                </select>
            </div>

            <div>
                <label for="media" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.add_media') }}</label>
                <input type="file" id="media" name="media[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs text-fb-muted dark:text-zinc-500 mt-1">{{ __('app.media_hint') }}</p>
                @error('media')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('groups.show', $group) }}" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</a>
                <x-button variant="primary">{{ __('app.write_post') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
    @vite('resources/js/chunked-upload.js')
@endpush
