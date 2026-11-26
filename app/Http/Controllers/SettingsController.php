<?php

namespace App\Http\Controllers;

use App\Models\{Friendship, User, UserBlock};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        return view('settings.index', [
            'user' => $user,
            'blockedUsers' => $user->blockedUsers()->latest()->get(),
        ]);
    }

    public function updatePrivacy(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'profile_locked' => ['required', 'boolean'],
        ]);

        $user->update(['profile_locked' => filter_var($validated['profile_locked'], FILTER_VALIDATE_BOOLEAN)]);

        $message = $user->isProfileLocked()
            ? __('app.profile_locked')
            : __('app.profile_unlocked');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'profile_locked' => $user->isProfileLocked(),
            ]);
        }

        return redirect()->route('settings.index')->with('status', $message);
    }

    public function blockUser(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $target = User::query()
            ->where(function ($q) use ($validated, $user): void {
                $q->where('email', $validated['identifier'])
                  ->orWhere('name', 'like', '%'.$validated['identifier'].'%');
            })
            ->where('id', '!=', $user->id)
            ->first();

        $alreadyBlocked = $target !== null && $user->hasBlocked($target);

        if (! $target) {
            $message = __('app.user_not_found');
        } elseif ($alreadyBlocked) {
            $message = __('app.user_already_blocked');
        } else {
            $user->blocks()->create([
                'blocked_user_id' => $target->id,
                'were_friends' => $user->isFriendWith($target),
            ]);

            // Remove existing friendship so the blocked user disappears from friends list
            Friendship::query()
                ->where(function ($q) use ($user, $target): void {
                    $q->where('user_id', $user->id)->where('friend_id', $target->id)
                        ->orWhere('user_id', $target->id)->where('friend_id', $user->id);
                })
                ->delete();

            $message = __('app.user_blocked', ['name' => $target->name]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $target !== null && ! $alreadyBlocked,
                'message' => $message,
            ]);
        }

        return redirect()->route('posts.index')
            ->with('status', $message);
    }

    public function blockProfileUser(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();

        if ($authUser->is($user)) {
            return back()->with('status', __('app.cannot_block_self'));
        }

        if ($authUser->hasBlocked($user)) {
            return back()->with('status', __('app.user_already_blocked'));
        }

        $authUser->blocks()->create([
            'blocked_user_id' => $user->id,
            'were_friends' => $authUser->isFriendWith($user),
        ]);

        // Remove existing friendship so the blocked user disappears from friends list
        Friendship::query()
            ->where(function ($q) use ($authUser, $user): void {
                $q->where('user_id', $authUser->id)->where('friend_id', $user->id)
                    ->orWhere('user_id', $user->id)->where('friend_id', $authUser->id);
            })
            ->delete();

        return redirect()->route('posts.index')
            ->with('status', __('app.user_blocked', ['name' => $user->name]));
    }

    public function unblockUser(Request $request, User $user): RedirectResponse
    {
        $authUser = auth()->user();

        $block = $authUser->blocks()->where('blocked_user_id', $user->id)->first();
        $wereFriends = (bool) ($block?->were_friends ?? false);

        $authUser->blocks()->where('blocked_user_id', $user->id)->delete();

        if ($wereFriends) {
            $this->restoreFriendship($authUser, $user);
        }

        return redirect()->route('settings.index')
            ->with('status', __('app.user_unblocked', ['name' => $user->name]));
    }

    public function unblockSelected(Request $request): RedirectResponse
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'blocked_user_id' => ['required', 'exists:user_blocks,blocked_user_id'],
        ]);

        $block = $authUser->blocks()->where('blocked_user_id', $validated['blocked_user_id'])->first();
        $wereFriends = (bool) ($block?->were_friends ?? false);

        $authUser->blocks()->where('blocked_user_id', $validated['blocked_user_id'])->delete();

        $blocked = User::find($validated['blocked_user_id']);

        if ($wereFriends && $blocked) {
            $this->restoreFriendship($authUser, $blocked);
        }

        return redirect()->route('settings.index')
            ->with('status', $blocked
                ? __('app.user_unblocked', ['name' => $blocked->name])
                : __('app.user_unblocked'));
    }

    private function restoreFriendship(User $authUser, User $user): void
    {
        $friendship = $authUser->friendshipWith($user);
        if ($friendship) {
            $friendship->update(['status' => 'accepted', 'accepted_at' => now()]);

            return;
        }

        Friendship::create([
            'user_id' => $authUser->id,
            'friend_id' => $user->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirm' => ['required', 'accepted'],
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            return back(fallback: route('settings.index'))
                ->withErrors(['password' => __('app.invalid_password')]);
        }

        // Collect uploaded files before cascading deletion removes their records.
        $files = collect();
        if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
            $avatarPath = str_replace('storage/', '', $user->avatar);
            $files->push([
                'disk' => str_starts_with($user->avatar, 'avatars/') && ! str_contains($avatarPath, '..')
                    ? 'public_root'
                    : 'public',
                'path' => $avatarPath,
            ]);
        }

        $postIds = $user->posts()->pluck('id');
        $files = $files->merge(\App\Models\PostMedia::query()
            ->whereIn('post_id', $postIds)->whereNotNull('path')->get(['disk', 'path']));
        $files = $files->merge(\App\Models\MessageMedia::query()
            ->where('user_id', $user->id)->whereNotNull('path')->get(['disk', 'path']));

        foreach ([$user->pages()->get(), $user->groups()->get()] as $ownedEntities) {
            foreach ($ownedEntities as $entity) {
                foreach ([$entity->avatar, $entity->cover_image] as $path) {
                    if ($path && ! str_starts_with($path, 'http')) {
                        $files->push(['disk' => 'public', 'path' => str_replace('storage/', '', $path)]);
                    }
                }
            }
        }

        $deleted = DB::transaction(function () use ($user): int {
            // Sessions and reset tokens are not constrained to users by foreign keys.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Delete the account row explicitly and only continue when it was removed.
            return DB::table('users')->where('id', $user->id)->delete();
        });

        if ($deleted !== 1) {
            return back(fallback: route('settings.index'))
                ->withErrors(['account' => __('app.account_delete_failed')]);
        }

        foreach ($files->unique(fn ($file) => $file['disk'].':'.$file['path']) as $file) {
            if ($file['disk'] === 'public_root') {
                File::delete(public_path($file['path']));
            } else {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('status', __('app.account_deleted'));
    }
}
