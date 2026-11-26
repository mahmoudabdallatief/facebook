use App\Models\User;
use App\Models\Friendship;
use Illuminate\Support\Facades\Hash;
use App\Models\PostMedia;
use App\Models\MessageMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
@extends('layouts.app')

@section('title', __('app.settings'))

@section('content')
<div class="space-y-6">
    @if (session('status'))
        <div role="status" class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('password') || $errors->has('confirm'))
        <div role="alert" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
            {{ $errors->first('password') ?: $errors->first('confirm') }}
        </div>
    @endif

    <div>
        <h1 class="text-2xl font-bold">{{ __('app.settings') }}</h1>
        <p class="mt-1 text-sm text-fb-muted dark:text-zinc-400">{{ __('app.settings_description') }}</p>
    </div>

    <!-- Privacy & Profile Lock -->
    <x-card :title="__('app.privacy_settings')">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-fb-text dark:text-zinc-100">{{ __('app.profile_lock') }}</h3>
                    <p class="text-sm text-fb-muted dark:text-zinc-500">{{ __('app.profile_lock_description') }}</p>
                </div>
                <form method="POST" action="{{ route('settings.privacy') }}" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <input type="hidden" name="profile_locked" value="1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="profile_locked" value="1"
                            class="sr-only peer"
                            {{ $user->isProfileLocked() ? 'checked' : '' }}
                            onchange="this.form.submit()">
                        <span class="w-11 h-6 rounded-full bg-zinc-200 peer peer-checked:bg-fb-blue after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:peer-checked:translate-x-full"></span>
                    </label>
                </form>
            </div>

            <p class="text-sm text-fb-muted dark:text-zinc-500 ml-1">
                {{ $user->isProfileLocked()
                    ? __('app.profile_lock_enabled')
                    : __('app.profile_lock_disabled') }}
            </p>

            @error('profile_locked')
                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </x-card>

    <!-- Blocked Users -->
    <x-card >
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-fb-text dark:text-zinc-100">{{ __('app.blocked_users') }}</h3>
                {{-- <button type="button" onclick="openModal('block-user-modal')"
                    class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover">
                    {{ __('app.block_user') }}
                </button> --}}
            </div>

            @if($blockedUsers->isNotEmpty())
                <p class="text-sm font-medium text-fb-text dark:text-zinc-200">{{ __('app.blocked_users_list') }}</p>
                <br>
                <!-- مربع البحث -->
                <div class="space-y-2">
                    <input type="text" id="blocked-search" placeholder="{{ __('app.search_blocked_users') }}"
                        class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                </div>
                <br>
                <!-- قائمة المحظورين مع خيار اختيار -->
                <div id="blocked-list" class="divide-y divide-fb-border dark:divide-zinc-800 mt-2 max-h-[400px] overflow-y-auto border rounded-lg p-2 bg-white dark:bg-zinc-900">
                    @foreach($blockedUsers as $blocked)
                        <div class="blocked-item flex items-center justify-between gap-3 py-2.5 px-2 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800 cursor-pointer"
                             data-name="{{ $blocked->name }}"
                             data-id="{{ $blocked->id }}">
                            <div class="flex items-center gap-3 w-full min-w-0">
                                <input type="radio" name="blocked_user_id" value="{{ $blocked->id }}"
                                    class="mt-0.5 shrink-0 text-fb-blue ring-offset-white focus:ring-fb-blue dark:ring-offset-zinc-900">
                                @if($blocked->avatarUrl())
                                    <img src="{{ $blocked->avatarUrl() }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-fb-blue text-xs font-bold text-white">{{ mb_substr($blocked->name, 0, 1) }}</span>
                                @endif
                                <span class="font-medium truncate block-list-name">{{ $blocked->name }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- زر إلغاء الحظر في الأسفل -->
                <form id="unblock-selected-form" method="POST" action="{{ route('settings.unblock-selected') }}" class="mt-4 space-y-2">
                    @csrf @method('DELETE')
                    <input type="hidden" name="blocked_user_id" id="unblock-selected-id">
                    <div class="flex justify-end">
                        <button type="button" id="unblock-selected-btn"
                            onclick="openUnblockConfirm()"
                            class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover disabled:bg-zinc-300 disabled:text-zinc-500 dark:disabled:bg-zinc-800 dark:disabled:text-zinc-400 focus:outline-none focus:ring-4 focus:ring-fb-blue/30">
                            {{ __('app.unblock_selected') }}
                        </button>
                    </div>
                </form>
            @else
                <p class="text-sm text-fb-muted dark:text-zinc-400 text-center py-4">{{ __('app.no_blocked_users') }}</p>
            @endif
        </div>
    </x-card>

    <!-- Delete Account -->
    <x-card :title="__('app.delete_account')">
        <div class="space-y-4">
            <p class="text-sm text-fb-muted dark:text-zinc-500">
                {{ __('app.delete_account_description') }}
            </p>
            <button type="button" onclick="openModal('delete-account-modal')" class="w-full inline-flex items-center justify-center rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40">
                {{ __('app.delete_my_account') }}
            </button>
        </div>
    </x-card>

    <!-- Block User Modal -->
    <x-modal id="block-user-modal" :title="__('app.block_user')" size="sm">
        <form method="POST" action="{{ route('settings.block') }}" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label for="block-identifier" class="block text-sm font-medium text-fb-text dark:text-zinc-200">
                    {{ __('app.block_user_placeholder') }}
                </label>
                <input type="text" id="block-identifier" name="identifier" required autofocus
                    class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    placeholder="{{ __('app.block_user_placeholder') }}">
                @error('identifier')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeModal('block-user-modal')" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                    {{ __('app.cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-fb-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-fb-blue-hover focus:outline-none focus:ring-4 focus:ring-fb-blue/30">
                    {{ __('app.block_user') }}
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Unblock Confirm Modal -->
    <x-modal id="unblock-confirm-modal" :title="__('app.unblock')" size="sm">
        <form id="unblock-confirm-form" method="POST" action="{{ route('settings.unblock-selected') }}" class="space-y-4">
            @csrf @method('DELETE')
            <input type="hidden" name="blocked_user_id" id="unblock-confirm-id">
            <p class="text-sm text-fb-text dark:text-zinc-200" id="unblock-confirm-text"></p>
            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeModal('unblock-confirm-modal')" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                    {{ __('app.cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500/30">
                    {{ __('app.unblock') }}
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal id="delete-account-modal" :title="__('app.delete_account_confirm')" size="sm">
        <form method="POST" action="{{ route('settings.delete-account') }}" class="space-y-4">
            @csrf @method('DELETE')
            <p class="text-sm text-fb-text dark:text-zinc-200">{{ __('app.delete_account_warning') }}</p>

            <div class="space-y-1">
                <label for="delete-password" class="block text-sm font-medium text-fb-text dark:text-zinc-200">{{ __('app.current_password') }}</label>
                <input type="password" id="delete-password" name="password" required
                    class="w-full rounded-lg border border-fb-border bg-white px-3 py-2 text-sm shadow-sm outline-none ring-fb-blue/30 focus:border-fb-blue focus:ring-4 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                @error('password')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <input type="checkbox" id="delete-confirm" name="confirm" value="1" required class="mt-0.5">
                <label for="delete-confirm" class="text-sm text-fb-text dark:text-zinc-200">
                    {{ __('app.confirm_delete_account') }}
                </label>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeModal('delete-account-modal')" class="rounded-lg bg-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                    {{ __('app.cancel') }}
                </button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500/30">
                    {{ __('app.delete_my_account') }}
                </button>
            </div>
        </form>
    </x-modal>
    <script>
        (function () {
            const searchInput = document.getElementById('blocked-search');
            const blockedList = document.getElementById('blocked-list');
            const radioButtons = document.querySelectorAll('input[name="blocked_user_id"]');
            const unblockBtn = document.getElementById('unblock-selected-btn');
            const unblockIdInput = document.getElementById('unblock-selected-id');
            const unblockForm = document.getElementById('unblock-selected-form');
            const unblockConfirmText = document.getElementById('unblock-confirm-text');
            const unblockConfirmIdInput = document.getElementById('unblock-confirm-id');

            if (!searchInput || !blockedList) return;

            searchInput.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();
                blockedList.querySelectorAll('.blocked-item').forEach(function (item) {
                    const name = item.dataset.name.toLowerCase();
                    item.style.display = name.includes(term) ? 'flex' : 'none';
                });
            });

            radioButtons.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    unblockIdInput.value = this.value;
                    unblockBtn.disabled = false;
                });
            });

            blockedList.querySelectorAll('.blocked-item').forEach(function (item) {
                item.addEventListener('click', function (e) {
                    if (e.target.tagName === 'INPUT' && e.target.type === 'radio') return;
                    const radio = item.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            });

            function openUnblockConfirm() {
                const checkedRadio = document.querySelector('input[name="blocked_user_id"]:checked');
                if (!checkedRadio) return;
                const name = checkedRadio.parentElement?.querySelector('.block-list-name')?.textContent || '';
                unblockConfirmText.textContent = '{{ addslashes(__('app.unblock_confirm', ['name' => ''])) }}'.replace(':name', name);
                unblockConfirmIdInput.value = checkedRadio.value;
                openModal('unblock-confirm-modal');
            }

            window.openUnblockConfirm = openUnblockConfirm;

            unblockForm.addEventListener('submit', function (e) {
                e.preventDefault();
                openUnblockConfirm();
            });

        })();
    </script>
    @if ($errors->has('password') || $errors->has('confirm'))
        <script>
            document.addEventListener('DOMContentLoaded', () => openModal('delete-account-modal'));
        </script>
    @endif
</div>
@endsection
