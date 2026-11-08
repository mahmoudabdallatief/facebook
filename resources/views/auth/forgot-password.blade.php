@extends('layouts.guest')

@section('title', __('app.forgot_password'))

@section('content')
<div class="mx-auto w-full max-w-md text-center">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-4xl font-bold text-fb-blue">{{ __('app.name') }}</a>
    </div>
    <x-card :title="__('app.forgot_password')" :center="true">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-3 text-start">
            @csrf
            <x-input name="email" type="email" :label="__('app.email')" required autofocus autocomplete="username" />
            <x-button class="w-full">{{ __('app.send_reset_link') }}</x-button>
        </form>
        <div class="mt-4 text-center text-sm">
            <a href="{{ route('login') }}" class="text-fb-blue hover:underline">{{ __('app.login') }}</a>
        </div>
    </x-card>
</div>
@endsection
