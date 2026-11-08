@extends('layouts.app')

@section('title', __('app.dashboard'))

@section('content')
<div class="grid gap-4 lg:grid-cols-[280px,1fr]">
    <x-card>
        <div class="flex items-center gap-3">
            @if(auth()->user()->avatarUrl())
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-12 w-12 rounded-full object-cover">
            @else
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-fb-blue text-lg font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
            @endif
            <div>
                <div class="font-semibold">{{ auth()->user()->name }}</div>
                <div class="text-sm text-fb-muted dark:text-zinc-400">{{ auth()->user()->email }}</div>
            </div>
        </div>
        @unless(auth()->user()->hasVerifiedEmail())
            <a href="{{ route('verification.notice') }}" class="mt-4 block text-sm text-fb-blue hover:underline">{{ __('app.verify_email') }}</a>
        @endunless
    </x-card>

    <x-card :title="__('app.home')">
        <p class="text-fb-muted dark:text-zinc-400">{{ __('app.welcome_sub') }}</p>
        <div class="mt-4 rounded-lg border border-dashed border-fb-border p-6 text-center text-sm text-fb-muted dark:border-zinc-700 dark:text-zinc-500">
            {{ __('app.feed_coming_soon') }}
        </div>
    </x-card>
</div>
@endsection
