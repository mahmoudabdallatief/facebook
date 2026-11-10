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
            <div class="flex-1 min-w-0">
                <div class="font-semibold truncate">{{ auth()->user()->name }}</div>
                <div class="text-sm text-fb-muted dark:text-zinc-400 truncate">{{ auth()->user()->email }}</div>
            </div>
            <button type="button" onclick="openModal('profile-edit-modal')" class="rounded-lg p-2 text-fb-muted hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('app.edit_profile') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </button>
        </div>
        @unless(auth()->user()->hasVerifiedEmail())
            <a href="{{ route('verification.notice') }}" class="mt-4 block text-sm text-fb-blue hover:underline">{{ __('app.verify_email') }}</a>
        @endunless
    </x-card>

    <x-card :title="__('app.home')">
        <p class="text-fb-muted dark:text-zinc-400">{{ __('app.welcome_sub') }}</p>
        <div class="mt-4 flex gap-3">
            <a href="{{ route('posts.index') }}" class="flex-1 inline-flex items-center justify-center rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">{{ __('app.timeline') }}</a>
            <a href="{{ route('posts.create') }}" class="inline-flex items-center justify-center rounded-lg bg-fb-green px-4 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-95">{{ __('app.write_post') }}</a>
        </div>
    </x-card>
</div>

<x-modal id="profile-edit-modal" :title="__('app.edit_profile')" size="lg">
    <form id="profile-edit-form" enctype="multipart/form-data" class="space-y-5">
        @csrf
        <input type="hidden" name="_method" value="PATCH">
        
        <div class="flex items-center gap-4">
            @if(auth()->user()->avatarUrl())
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" id="avatar-preview" class="h-20 w-20 rounded-full object-cover">
            @else
                <div id="avatar-preview" class="flex h-20 w-20 items-center justify-center rounded-full bg-fb-blue text-2xl font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
            @endif
            <div>
                <label for="avatar" class="cursor-pointer rounded-lg bg-fb-blue px-4 py-2 text-sm font-medium text-white hover:bg-fb-blue-hover">
                    {{ __('app.change_avatar') }}
                </label>
                <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)" class="sr-only">
                <p class="mt-1 text-xs text-fb-muted dark:text-zinc-500">{{ __('app.profile_photo_hint') }}</p>
            </div>
        </div>

        <x-input type="text" name="name" :value="auth()->user()->name" :label="__('app.name_field')" required autofocus autocomplete="name" />

        <div class="space-y-1">
            <label for="bio" class="block text-sm font-medium text-fb-muted dark:text-zinc-300">{{ __('app.bio') }}</label>
            <textarea
                id="bio"
                name="bio"
                rows="4"
                class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900"
                placeholder="{{ __('app.bio_placeholder') }}"
            >{{ auth()->user()->bio }}</textarea>
            <p class="text-xs text-fb-muted dark:text-zinc-500">{{ __('app.bio_hint') }}</p>
        </div>

        <div class="pt-2 flex justify-end gap-3">
            <button type="button" onclick="closeModal('profile-edit-modal')" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">{{ __('app.cancel') }}</button>
            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover focus:outline-none focus:ring-4 focus:ring-fb-blue/30" id="profile-save-btn">
                {{ __('app.save_changes') }}
            </button>
        </div>
    </form>
</x-modal>

<script>
    document.getElementById('profile-edit-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const saveBtn = document.getElementById('profile-save-btn');
        const originalText = saveBtn.innerHTML;
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> {{ __('app.saving') }};
        
        try {
            const response = await fetch('{{ route('profile.update') }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData,
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Redirect to dashboard on success
                window.location.href = '{{ route('dashboard') }}';
            } else {
                // Show validation errors
                if (data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        showToast(messages[0], 'error');
                    });
                } else if (data.message) {
                    showToast(data.message, 'error');
                }
            }
        } catch (error) {
            showToast('{{ __('app.something_went_wrong') }}', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    });
    
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
        toast.className = `fixed bottom-4 right-4 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-slide-in`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>

<style>
    @keyframes slide-in {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .animate-slide-in { animation: slide-in 0.3s ease-out; }
</style>
@endsection