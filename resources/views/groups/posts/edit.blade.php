@extends('layouts.app')

@section('title', __('app.edit_post').' · '.$group->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card :title="__('app.edit_post')">
        <form method="POST" action="{{ route('groups.posts.update', ['group' => $group, 'post' => $post]) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="space-y-1">
                <label for="body" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ $post->isShare() ? __('app.share_placeholder') : __('app.post_placeholder') }}</label>
                <textarea
                    id="body"
                    name="body"
                    rows="6"
                    class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900"
                    @unless($post->isShare()) required @endunless autofocus>{{ old('body', $post->body) }}</textarea>
                @error('body')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="visibility" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.visibility') }}</label>
                <select id="visibility" name="visibility" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="public" {{ $post->visibility === 'public' ? 'selected' : '' }}>{{ __('app.visibility_public') }}</option>
                    <option value="friends" {{ $post->visibility === 'friends' ? 'selected' : '' }}>{{ __('app.visibility_friends') }}</option>
                </select>
            </div>

            @if(!$post->isShare() && $post->media->isNotEmpty())
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.existing_media') }}</label>
                    <div class="grid gap-2 grid-cols-1 sm:grid-cols-2 md:grid-cols-3">
                        @foreach($post->media as $media)
                            <div class="relative group" data-media-id="{{ $media->id }}">
                                @if($media->type === 'video')
                                    <video src="{{ $media->url }}" controls class="h-48 w-full object-cover rounded-lg"></video>
                                @else
                                    <img src="{{ $media->url }}" alt="" class="h-48 w-full object-cover rounded-lg">
                                @endif
                                <button type="button" class="absolute top-1 right-1 z-10 bg-red-600/90 text-white p-1.5 rounded hover:bg-red-600 transition-colors opacity-0 group-hover:opacity-100" aria-label="{{ __('app.remove') }}" onclick="removeExistingMedia(this)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                <label class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg cursor-pointer">
                                    <input type="checkbox" name="keep_media[]" value="{{ $media->id }}" checked class="sr-only keep-media-checkbox" data-media-id="{{ $media->id }}">
                                    <div class="flex items-center justify-center gap-1 bg-white/90 backdrop-blur text-zinc-900 text-xs font-medium rounded">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                        {{ __('app.remove') }}
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.media_hint') }}</p>
                    <p id="media-slot-counter" class="text-xs text-fb-muted dark:text-zinc-500 mt-1"></p>
                    @error('media')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            @endif

            @unless($post->isShare())
                <div>
                    <label for="media" class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.add_media') }}</label>
                    <input type="file" id="media" name="media[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                </div>
            @endunless

            <div class="pt-4 border-t border-fb-border dark:border-zinc-800 flex justify-end gap-3">
                <a href="{{ route('groups.posts.show', ['group' => $group, 'post' => $post]) }}" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</a>
                <x-button variant="primary">{{ __('app.save_changes') }}</x-button>
                <form method="POST" action="{{ route('groups.posts.destroy', ['group' => $group, 'post' => $post]) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('app.delete_post') }}</button>
                </form>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
    @vite('resources/js/chunked-upload.js')
    <script>
        function removeExistingMedia(btn) {
            const card = btn.closest('[data-media-id]');
            const mediaId = card.dataset.mediaId;
            const checkbox = document.querySelector('.keep-media-checkbox[data-media-id="' + mediaId + '"]');
            if (checkbox) checkbox.checked = false;
            card.style.display = 'none';
            updateMediaSlotCounter();
        }

        const counterTemplate = '{{ __('app.media_slots_remaining', [':remaining' => '___REMAINING___', ':total' => '___TOTAL___']) }}';
        function updateMediaSlotCounter() {
            const kept = document.querySelectorAll('.keep-media-checkbox:checked').length;
            const total = 10;
            const remaining = total - kept;
            const el = document.getElementById('media-slot-counter');
            if (el) el.textContent = counterTemplate.replace('___REMAINING___', remaining).replace('___TOTAL___', total);
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateMediaSlotCounter();
            document.querySelectorAll('.keep-media-checkbox').forEach(function (cb) {
                cb.addEventListener('change', updateMediaSlotCounter);
            });
        });
    </script>
@endpush
