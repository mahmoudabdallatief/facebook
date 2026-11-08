@extends('layouts.guest')

@section('title', __('app.reset_password'))

@section('content')
<div class="mx-auto w-full max-w-md text-center">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-4xl font-bold text-fb-blue">{{ __('app.name') }}</a>
    </div>
    <x-card :title="__('app.reset_password')" :center="true">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-3 text-start">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-input name="email" type="email" :label="__('app.email')" :value="$email" required autofocus autocomplete="username" />
            <x-input name="password" type="password" :label="__('app.password')" required autocomplete="new-password" />
            <x-input name="password_confirmation" type="password" :label="__('app.confirm_password')" required autocomplete="new-password" />
            <x-button class="w-full">{{ __('app.reset_password') }}</x-button>
        </form>
    </x-card>
</div>
@endsection
