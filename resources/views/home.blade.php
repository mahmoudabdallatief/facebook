@extends('layouts.guest')

@section('title', __('app.name'))

@section('content')
<div class="grid w-full gap-8 lg:grid-cols-2 lg:items-center">
    <div class="text-center lg:text-start">
        <h1 class="text-4xl font-bold text-fb-blue lg:text-5xl">{{ __('app.name') }}</h1>
        <p class="mt-3 text-lg text-fb-muted dark:text-zinc-300">{{ __('app.welcome') }}</p>
        <p class="mt-1 text-fb-muted dark:text-zinc-400">{{ __('app.welcome_sub') }}</p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3 lg:justify-start">
            <a href="{{ route('locale.switch', 'ar') }}" class="rounded-lg border border-fb-border px-3 py-1.5 text-sm hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-900">العربية</a>
            <a href="{{ route('locale.switch', 'en') }}" class="rounded-lg border border-fb-border px-3 py-1.5 text-sm hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-900">English</a>
            <button type="button" onclick="toggleTheme()" class="rounded-lg border border-fb-border px-3 py-1.5 text-sm hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-900">◐ {{ app()->getLocale() === 'ar' ? 'الوضع' : 'Theme' }}</button>
        </div>
    </div>

    <x-card class="mx-auto w-full max-w-md">
        @auth
            <p class="mb-4 text-sm text-fb-muted dark:text-zinc-400">{{ __('app.welcome') }}, {{ auth()->user()->name }}</p>
            <a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-lg bg-fb-blue px-4 py-2.5 font-semibold text-white hover:bg-fb-blue-hover">{{ __('app.dashboard') }}</a>
        @else
            <form method="POST" action="{{ route('login') }}" class="space-y-3">
                @csrf
                <x-input name="email" type="email" :label="__('app.email')" required autofocus autocomplete="username" />
                <x-input name="password" type="password" :label="__('app.password')" required autocomplete="current-password" />
                <label class="flex items-center gap-2 text-sm text-fb-muted dark:text-zinc-400">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-fb-border text-fb-blue focus:ring-fb-blue/30">
                    <span>{{ __('app.remember_me') }}</span>
                </label>
                <x-button class="w-full">{{ __('app.login') }}</x-button>
                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="text-sm text-fb-blue hover:underline">{{ __('app.forgot_password') }}</a>
                </div>
                <hr class="border-fb-border dark:border-zinc-800">
                <div class="text-center">
                    <a href="{{ route('register') }}" class="inline-flex rounded-lg bg-fb-green px-4 py-2.5 font-semibold text-white hover:brightness-95">{{ __('app.register') }}</a>
                </div>
            </form>
        @endauth
    </x-card>
</div>
@endsection
