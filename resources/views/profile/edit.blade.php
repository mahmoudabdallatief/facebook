@extends('layouts.app')

@section('title', __('app.edit_profile'))

@section('content')
<div class="mx-auto max-w-xl">
    <x-card :title="__('app.edit_profile')">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
            @method('PATCH')
            @csrf

            <div class="flex items-center gap-4">
                @if($user->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" id="avatar-preview" class="h-20 w-20 rounded-full object-cover">
                @else
                    <div id="avatar-preview" class="flex h-20 w-20 items-center justify-center rounded-full bg-fb-blue text-2xl font-bold text-white">{{ mb_substr($user->name, 0, 1) }}</div>
                @endif
                <div>
                    <label for="avatar" class="cursor-pointer rounded-lg bg-fb-blue px-4 py-2 text-sm font-medium text-white hover:bg-fb-blue-hover">
                        {{ __('app.change_avatar') }}
                    </label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)" class="sr-only">
                    <p class="mt-1 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.profile_photo_hint') }}</p>
                </div>
            </div>

            <x-input type="text" name="name" :value="$user->name" :label="__('app.name_field')" required autofocus autocomplete="name" />

            <div class="space-y-1">
                <label for="bio" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.bio') }}</label>
                <textarea
                    id="bio"
                    name="bio"
                    rows="4"
                    class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900"
                    placeholder="{{ __('app.bio_placeholder') }}"
                >{{ old('bio', $user->bio) }}</textarea>
                @error('bio')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.bio_hint') }}</p>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</a>
                <x-button variant="primary">{{ __('app.save_changes') }}</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection