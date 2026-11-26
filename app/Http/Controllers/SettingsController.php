<?php

namespace App\Http\Controllers;

use App\Models\{Friendship, User, UserBlock};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'confirm' => ['required', 'boolean', 'accepted'],
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            return back(fallback: route('settings.index'))
                ->withErrors(['password' => __('app.invalid_password')]);
        }

        // تسجيل الخروج وإبطال الجلسة قبل حذف الحساب لتجنب إعادة الصفقة
        // المفتوحة في بيئة الاختبار (RefreshDatabase + SQLite).
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->blockedBy()->delete();
        $user->blocks()->delete();
        $user->delete();

        return redirect()->route('home')
            ->with('status', __('app.account_deleted'));
    }
}
