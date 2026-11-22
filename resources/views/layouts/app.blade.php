<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="{{ (auth()->user() ? auth()->user()->theme : ($theme ?? session('theme', 'light'))) === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <script>window.__initialTheme = @json(auth()->user() ? auth()->user()->theme : ($theme ?? session('theme', 'light')));</script>
    <script>
        window.LARAVEL_USER_ID = @json(auth()->id());
        window.MESSENGER_TRANSLATIONS = {
            messenger: {
                no_more_messages: @json(__('messenger.no_more_messages')),
                js: @json(__('messenger.js')),
            },
        };
        window.APP_LOCALE = @json(app()->getLocale());
        window.postUploadRoutes = {
            initiate: @json(route('uploads.initiate')),
            chunk: @json(route('uploads.chunk')),
            status: @json(route('uploads.status')),
            cancel: @json(route('uploads.cancel')),
        };
        window.POST_UPLOAD_TEXT = {
            maxFiles: @json(__('app.upload_max_files')),
            maxSize: @json(__('app.upload_max_size')),
            invalidType: @json(__('app.upload_invalid_type')),
            wait: @json(__('app.upload_wait')),
            failed: @json(__('app.upload_failed')),
            waiting: @json(__('app.upload_waiting')),
            uploading: @json(__('app.upload_progress')),
            complete: @json(__('app.upload_complete')),
            remove: @json(__('app.upload_remove')),
        };
        window.REVERB_APP_KEY = @json(env('REVERB_APP_KEY'));
        window.REVERB_HOST = @json(env('REVERB_HOST'));
        window.REVERB_PORT = @json(env('REVERB_PORT'));
        window.REVERB_SCHEME = @json(env('REVERB_SCHEME'));
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @auth
        @vite(['resources/js/messenger.js'])
    @endauth
    @stack('scripts')
</head>
<body class="min-h-screen bg-fb-bg text-fb-text dark:bg-zinc-950 dark:text-zinc-100 font-sans antialiased">
    @include('layouts.partials.nav')
    <main class="mx-auto w-full max-w-5xl px-4 pt-20 pb-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
