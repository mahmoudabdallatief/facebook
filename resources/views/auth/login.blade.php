@extends('layouts.guest')

@section('title', __('app.login'))

@section('content')
<div class="mx-auto w-full max-w-md text-center">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-4xl font-bold text-fb-blue">{{ __('app.name') }}</a>
    </div>
    <x-card :center="true">
        <form method="POST" action="{{ route('login') }}" class="space-y-3 text-start">
            @csrf
            <x-input name="email" type="email" :label="__('app.email')" required autofocus autocomplete="username" />
            <x-input name="password" type="password" :label="__('app.password')" required autocomplete="current-password" />
            <label class="flex items-center gap-2 text-sm text-fb-muted dark:text-zinc-400">
                <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-fb-border text-fb-blue focus:ring-fb-blue/30">
                <span>{{ __('app.remember_me') }}</span>
            </label>
            <x-button class="w-full">{{ __('app.login') }}</x-button>
        </form>
        <div class="mt-4 text-center">
            <a href="{{ route('password.request') }}" class="text-sm text-fb-blue hover:underline">{{ __('app.forgot_password') }}</a>
        </div>
        <hr class="my-4 border-fb-border dark:border-zinc-800">
        <div class="text-center">
            <a href="{{ route('register') }}" class="inline-flex rounded-lg bg-fb-green px-4 py-2.5 font-semibold text-white hover:brightness-95">{{ __('app.register') }}</a>
        </div>
    </x-card>
</div>
@endsection
