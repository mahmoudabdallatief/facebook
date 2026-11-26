@extends('layouts.app')

@section('title', $profileUser->name)

@section('content')
<div class="mx-auto max-w-2xl">
    <x-card center>
        <div class="flex flex-col items-center gap-4">
            @if($profileUser->avatarUrl())
                <img src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->name }}" class="h-24 w-24 rounded-full border-4 border-fb-surface object-cover dark:border-zinc-900">
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-fb-surface bg-fb-blue text-3xl font-bold text-white dark:border-zinc-900">{{ mb_substr($profileUser->name, 0, 1) }}</div>
            @endif

            <div class="text-center">
                <h1 class="text-2xl font-bold">{{ $profileUser->name }}</h1>
                <div class="mt-2 text-fb-muted dark:text-zinc-400 text-sm">{{ __('app.profile_lock_enabled') }}</div>
            </div>
        </div>
    </x-card>
</div>
@endsection
