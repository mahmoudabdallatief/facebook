@extends('layouts.app')

@section('title', __('app.edit_post'))

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card :title="__('app.edit_post')">
        <form method="POST" action="{{ route('posts.update', $post) }}" enctype="multipart/form-data" class="space-y-5">
            @method('PATCH')
            @csrf

            <div class="space-y-1">
                <label for="body" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.post_placeholder') }}</label>
                <textarea
                    id="body"
                    name="body"
                    rows="6"
                    class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900"
                    required autofocus>{{ old('body', $post->body) }}</textarea>
                @error('body')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.media_hint') }}</p>
            </div>

            <div>
                <label for="visibility" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.visibility') }}</label>
                <select id="visibility" name="visibility" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="public" {{ old('visibility', $post->visibility) === 'public' ? 'selected' : '' }}>{{ __('app.visibility_public') }}</option>
                    <option value="friends" {{ old('visibility', $post->visibility) === 'friends' ? 'selected' : '' }}>{{ __('app.visibility_friends') }}</option>
                </select>
            </div>

            @if($post->media->isNotEmpty())
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.existing_media') }}</label>
                    <div class="grid gap-2 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($post->media as $media)
                            <div class="relative rounded-lg border border-fb-border dark:border-zinc-700">
                                @if($media->type === 'video')
                                    <video src="{{ $media->url }}" controls preload="metadata" playsinline class="w-full aspect-video object-cover bg-zinc-100 dark:bg-zinc-800"></video>
                                @else
                                    <img src="{{ $media->url }}" alt="" class="w-full aspect-video object-cover">
                                @endif
                                <div class="absolute top-2 right-2 flex items-center justify-center">
                                    <label class="bg-black/60 text-white p-2 rounded-full hover:bg-red-600 transition-colors cursor-pointer">
                                        <input type="checkbox" name="keep_media[]" value="{{ $media->id }}" checked class="sr-only peer">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M16.5 4.478v.227a48.48 48.48 0 003.878.512.75.75 0 11-.383-1.282A48.82 48.82 0 0011.25 2c-1.307 0-2.595.062-3.881.329a.75.75 0 00-.383 1.282A48.48 48.48 0 0011.25 4.705a.75.75 0 001.5 0V4.478a.75.75 0 011.5 0zm-2.5 5.5c0-1.308 0-2.595.062-3.882A48.63 48.63 0 0111.25 8c1.308 0 2.595.062 3.881.328a.75.75 0 01-.383 1.282A48.48 48.48 0 0011.25 9.293a48.48 48.48 0 003.878-.513v.227a.75.75 0 01-.152 1.498l-1.5-4a.75.75 0 01-.383-1.123c.079-.207.168-.398.264-.577a.75.75 0 011.282-.091c.207.08.398.168.577.264a.75.75 0 01-.091 1.282l-1.5-4a.75.75 0 01-.152 1.498c0 .572-.236 1.143-.66 1.578a.75.75 0 00-.252.699c-.302.156-.64.195-.973.195-.333 0-.671-.039-1.0-.156a.75.75 0 00-.252.699c.424.435.916.698 1.556.698 1.308 0 2.595-.062 3.881-.328a.75.75 0 00-.383-1.282A48.82 48.82 0 0111.25 14.292a.75.75 0 00-1.5 0v2.707a.75.75 0 01-1.5 0V11.5a48.48 48.48 0 00-3.878-.513v-.227a.75.75 0 00-.152-1.498l1.5-4a.75.75 0 00.383 1.123c-.079.207-.168.398-.264.577a.75.75 0 00-1.282.091c-.207-.08-.398-.168-.577-.264a.75.75 0 00.091-1.282l1.5-4a.75.75 0 00.152-1.498c0-.572.236-1.143.66-1.578a.75.75 0 01.252-.699c.302-.156.64-.195.973-.195.333 0 .671.039 1 .156a.75.75 0 01.252-.699c-.424-.435-.916-.698-1.556-.698z" clip-rule="evenodd"/></svg>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label for="media" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.add_media') }}</label>
                <input type="file" id="media" name="media[]" multiple accept="image/*,video/*" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div id="media-previews" class="space-y-3 mt-3"></div>
                <p class="text-xs text-fb-muted dark:text-zinc-500 mt-1">{{ __('app.media_hint') }}</p>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</a>
                <x-button variant="primary">{{ __('app.save_changes') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
    @vite('resources/js/chunked-upload.js')
@endpush