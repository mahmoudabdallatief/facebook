<?php

namespace App\Http\Controllers;

use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

use App\Models\{Friendship, PostMedia, User};

class FriendController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $friends = $user->friends();

        $pendingIncoming = Friendship::query()
            ->with('user')
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $pendingOutgoing = Friendship::query()
            ->with('friend')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $relatedIds = Friendship::query()
            ->where('user_id', $user->id)
            ->orWhere('friend_id', $user->id)
            ->get()
            ->flatMap(fn (Friendship $f) => [$f->user_id, $f->friend_id])
            ->push($user->id)
            ->unique()
            ->all();

        $suggestions = User::query()
            ->whereNotIn('id', $relatedIds)
            ->inRandomOrder()
            ->limit(12)
            ->get();

        return view('friends.index', [
            'friends' => $friends,
            'pendingIncoming' => $pendingIncoming,
            'pendingOutgoing' => $pendingOutgoing,
            'suggestions' => $suggestions,
        ]);
    }

    public function send(Request $request, User $user): RedirectResponse
    {
        $auth = $request->user();
        if ($auth->id === $user->id) {
            return back()->with('status', __('app.cannot_friend_self'));
        }

        $existing = $auth->friendshipWith($user);
        if ($existing) {
            if ($existing->status === 'accepted') {
                return back()->with('status', __('app.already_friends'));
            }
            if ($existing->status === 'pending') {
                // if they sent to us, accept; else already pending
                if ($existing->friend_id === $auth->id) {
                    $existing->update(['status' => 'accepted', 'accepted_at' => now()]);

                    return back()->with('status', __('app.friend_request_accepted'));
                }

                return back()->with('status', __('app.friend_request_pending'));
            }
        }

        Friendship::create([
            'user_id' => $auth->id,
            'friend_id' => $user->id,
            'status' => 'pending',
        ]);

        return back()->with('status', __('app.friend_request_sent'));
    }

    public function accept(Request $request, Friendship $friendship): RedirectResponse
    {
        $auth = $request->user();
        if ($friendship->friend_id !== $auth->id || $friendship->status !== 'pending') {
            abort(403);
        }
        $friendship->update(['status' => 'accepted', 'accepted_at' => now()]);

        return back()->with('status', __('app.friend_request_accepted'));
    }

    public function reject(Request $request, Friendship $friendship): RedirectResponse
    {
        $auth = $request->user();
        if ($friendship->friend_id !== $auth->id) {
            abort(403);
        }
        $friendship->delete();

        return back()->with('status', __('app.friend_request_rejected'));
    }

    public function cancel(Request $request, Friendship $friendship): RedirectResponse
    {
        $auth = $request->user();
        if ($friendship->user_id !== $auth->id || $friendship->status !== 'pending') {
            abort(403);
        }
        $friendship->delete();

        return back()->with('status', __('app.friend_request_cancelled'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $auth = $request->user();
        $friendship = $auth->friendshipWith($user);
        if ($friendship) {
            $friendship->delete();
        }

        return back()->with('status', __('app.friend_removed'));
    }
}
