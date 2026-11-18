@extends('layouts.app')

@section('title', __('messenger.title'))

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 h-[calc(100vh-14rem)] min-h-[600px]">
    <aside class="lg:col-span-1 border border-fb-border rounded-xl bg-fb-surface dark:border-zinc-700 dark:bg-zinc-900 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold">{{ __('messenger.chats') }}</h2>
            <button type="button" onclick="openNewChatModal()" class="p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-fb-muted" aria-label="{{ __('messenger.new_chat') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
        </div>

        <div class="p-3 border-b border-fb-border dark:border-zinc-700">
            <div class="relative">
                <input type="text" id="conversation-search" placeholder="{{ __('messenger.search_placeholder') }}" class="w-full pl-10 pr-4 py-2 rounded-lg border border-fb-border bg-fb-bg dark:bg-zinc-800 dark:border-zinc-700 focus:ring-2 focus:ring-fb-blue focus:border-transparent" autocomplete="off">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-fb-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto" id="conversations-list">
            @if($conversations->isEmpty())
                <div class="p-8 text-center text-fb-muted">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <p class="text-lg font-medium">{{ __('messenger.no_conversations') }}</p>
                    <p class="text-sm mt-1">{{ __('messenger.no_conversations_sub') }}</p>
                    <button type="button" onclick="openNewChatModal()" class="mt-4 rounded-lg bg-fb-blue px-4 py-2 text-white text-sm hover:bg-fb-blue-hover">{{ __('messenger.start_new_chat') }}</button>
                </div>
            @else
                @foreach($conversations as $conversation)
                    @include('messenger.components.conversation-item', ['conversation' => $conversation])
                @endforeach

                @if($conversations->hasMorePages())
                    <div class="p-4 text-center">
                        <button type="button" onclick="loadMoreConversations(event)" class="text-fb-blue hover:underline text-sm">{{ __('messenger.load_more') }}</button>
                    </div>
                @endif
            @endif
        </div>
    </aside>

    <main class="lg:col-span-3 border border-fb-border rounded-xl bg-fb-surface dark:border-zinc-700 dark:bg-zinc-900 flex flex-col overflow-hidden relative" id="conversation-area">
        <div id="welcome-screen" class="flex-1 flex flex-col items-center justify-center p-8 text-center">
            <svg class="w-24 h-24 text-fb-muted/50 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <h2 class="text-xl font-semibold mb-2">{{ __('messenger.select_conversation') }}</h2>
            <p class="text-fb-muted">{{ __('messenger.select_conversation_sub') }}</p>
        </div>

        <div id="conversation-screen" class="hidden flex-1 flex flex-col">
        </div>
    </main>
</div>

<div id="new-chat-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
    <div class="w-full max-w-md bg-fb-surface dark:bg-zinc-900 rounded-xl shadow-xl border border-fb-border dark:border-zinc-700 overflow-hidden">
        <div class="p-4 border-b border-fb-border dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold">{{ __('messenger.new_chat') }}</h3>
            <button type="button" onclick="closeNewChatModal()" class="p-2 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-fb-muted" aria-label="{{ __('app.close') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-4">
            <div class="relative mb-4">
                <input type="text" id="new-chat-search" placeholder="{{ __('messenger.search_friends') }}" class="w-full pl-10 pr-4 py-2 rounded-lg border border-fb-border bg-fb-bg dark:bg-zinc-800 dark:border-zinc-700 focus:ring-2 focus:ring-fb-blue focus:border-transparent" autocomplete="off">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-fb-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <div id="new-chat-results" class="max-h-60 overflow-y-auto space-y-2">
            </div>
        </div>
    </div>
</div>

@endsection
