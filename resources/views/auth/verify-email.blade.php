@extends('layouts.app')

@section('title', __('app.verify_email'))

@section('content')
<div class="mx-auto max-w-lg">
    <x-card :title="__('app.verify_email')">
        <p class="text-sm text-fb-muted dark:text-zinc-400">{{ __('app.verify_notice') }}</p>

        @if (session('status') === 'verification-link-sent' || session('status') === __('app.verification_sent'))
            <div class="mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-200">
                {{ __('app.verification_sent') }}
            </div>
        @endif

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-button>{{ __('app.resend_verification') }}</x-button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button variant="secondary">{{ __('app.logout') }}</x-button>
            </form>
        </div>
    </x-card>
</div>
@endsection
