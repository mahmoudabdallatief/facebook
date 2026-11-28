@extends('layouts.app')

@section('title', __('app.edit').' '.$group->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card :title="__('app.edit').' '.$group->name">
        <form method="POST" action="{{ route('groups.update', $group) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PATCH')

            <div class="space-y-1">
                <label for="name" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.group_name') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $group->name) }}" required autofocus class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                @error('name')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="slug" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.page_slug') }}</label>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $group->slug) }}" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                @error('slug')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="description" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.page_description') }}</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">{{ old('description', $group->description) }}</textarea>
            </div>

            <div class="space-y-1">
                <label for="privacy" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.page_privacy') }}</label>
                <select id="privacy" name="privacy" class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <option value="public" {{ $group->privacy === 'public' ? 'selected' : '' }}>{{ __('app.page_privacy_public') }}</option>
                    <option value="private" {{ $group->privacy === 'private' ? 'selected' : '' }}>{{ __('app.page_privacy_private') }}</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.page_cover') }}</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    @if($group->cover_image)
                        <div class="mt-2">
                            <img src="{{ $group->coverUrl() }}" alt="" class="h-24 w-full object-cover rounded-lg border border-fb-border dark:border-zinc-800">
                            <label class="block mt-2 cursor-pointer text-xs text-fb-blue hover:underline">
                                <input type="checkbox" name="remove_cover" value="1" class="mr-1"> {{ __('app.remove_cover') }}
                            </label>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-fb-muted dark:text-zinc-300 mb-2">{{ __('app.page_avatar') }}</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" class="w-full p-2 rounded-lg border border-fb-border bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    @if($group->avatar)
                        <div class="mt-2">
                            <img src="{{ $group->avatarUrl() }}" alt="" class="h-24 w-24 object-cover rounded-full border border-fb-border dark:border-zinc-800 mx-auto">
                            <label class="block mt-2 cursor-pointer text-xs text-fb-blue hover:underline">
                                <input type="checkbox" name="remove_avatar" value="1" class="mr-1"> {{ __('app.remove_avatar') }}
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-4 border-t border-fb-border dark:border-zinc-800 flex justify-end gap-3">
                <a href="{{ route('groups.show', $group) }}" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</a>
                <x-button variant="primary">{{ __('app.save_changes') }}</x-button>
            </div>
        </form>
        <form method="POST" action="{{ route('groups.destroy', $group) }}" class="mt-3 flex justify-end">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('app.delete') }}</button>
        </form>
    </x-card>
</div>
@endsection
