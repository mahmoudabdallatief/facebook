@extends('layouts.guest')

@section('title', __('app.register'))

@section('content')
<div class="mx-auto w-full max-w-md text-center">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-4xl font-bold text-fb-blue">{{ __('app.name') }}</a>
    </div>
    <x-card :title="__('app.register')" :center="true">
        <form method="POST" action="{{ route('register') }}" class="space-y-3 text-start" enctype="multipart/form-data">
            @csrf
            <div class="space-y-1">
                <label class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.profile_photo') }}</label>
                <div class="flex items-center gap-3">
                    <img id="avatar-preview" src="{{ asset('images/facebook-logo.svg') }}" alt="" class="h-14 w-14 rounded-full border border-fb-border object-cover dark:border-zinc-700" width="56" height="56">
                    <div class="min-w-0 flex-1">
                        <input id="avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-fb-muted file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-200 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-zinc-800 hover:file:bg-zinc-300 dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-100 dark:file:hover:bg-zinc-700" onchange="window.previewAvatar && window.previewAvatar(this)">
                        <p class="mt-1 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.profile_photo_hint') }}</p>
                        @error('avatar') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
            <x-input name="name" :label="__('app.name_field')" required autofocus autocomplete="name" />
            <x-input name="email" type="email" :label="__('app.email')" required autocomplete="username" />
            <x-input name="password" type="password" :label="__('app.password')" required autocomplete="new-password" />
            <x-input name="password_confirmation" type="password" :label="__('app.confirm_password')" required autocomplete="new-password" />
            <x-button variant="green" class="w-full">{{ __('app.register') }}</x-button>
        </form>
        <div class="mt-4 text-center text-sm">
            <a href="{{ route('login') }}" class="text-fb-blue hover:underline">{{ __('app.login') }}</a>
        </div>
    </x-card>
</div>
@endsection
