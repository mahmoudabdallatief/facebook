<?php

namespace App\Http\Controllers;

use Illuminate\Http\{RedirectResponse, Request, JsonResponse};
use Illuminate\View\View;

use App\Models\{Friendship, PostMedia, User};

class FriendController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $userId = $user->id;
        $user->loadMissing(['blocks', 'blockedBy']);

        $friendships = Friendship::query()
            ->with(['user', 'friend'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId): void {
                $q->where('user_id', $userId)->orWhere('friend_id', $userId);
            })
            ->latest('accepted_at')
            ->paginate(10, ['*'], 'friends_page')
            ->withQueryString();

        $friends = $friendships->getCollection()->map(function (Friendship $f) use ($user): User {
            return $f->user_id === $user->id ? $f->friend : $f->user;
        });

        $pendingIncoming = Friendship::query()
            ->with('user')
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->paginate(10, ['*'], 'incoming_page')
            ->withQueryString();

        $pendingOutgoing = Friendship::query()
            ->with('friend')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->paginate(10, ['*'], 'outgoing_page')
            ->withQueryString();

        $relatedIds = Friendship::query()
            ->where('user_id', $userId)
            ->orWhere('friend_id', $userId)
            ->get()
            ->flatMap(fn (Friendship $f) => [$f->user_id, $f->friend_id])
            ->push($user->id)
            ->unique()
            ->all();

        $suggestions = User::query()
            ->whereNotIn('id', $relatedIds)
            ->latest()
            ->paginate(10, ['*'], 'suggestions_page')
            ->withQueryString();

        return view('friends.index', [
            'friends' => $friends,
            'friendships' => $friendships,
            'pendingIncoming' => $pendingIncoming,
            'pendingOutgoing' => $pendingOutgoing,
            'suggestions' => $suggestions,
        ]);
    }

    public function friendsInfinite(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $friendships = Friendship::query()
            ->with(['user', 'friend'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId): void {
                $q->where('user_id', $userId)->orWhere('friend_id', $userId);
            })
            ->latest('accepted_at')
            ->paginate(10, ['*'], 'page', (int) $request->get('page', 1));

        $html = view('friends._friends', [
            'friendships' => $friendships,
            'viewer' => $user,
        ])->render();

        return response()->json([
            'html' => $html,
            'next_page' => $friendships->hasMorePages() ? $friendships->currentPage() + 1 : null,
            'has_more' => $friendships->hasMorePages(),
        ]);
    }

    public function suggestionsInfinite(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $relatedIds = Friendship::query()
            ->where('user_id', $userId)
            ->orWhere('friend_id', $userId)
            ->get()
            ->flatMap(fn (Friendship $f) => [$f->user_id, $f->friend_id])
            ->push($user->id)
            ->unique()
            ->all();

        $users = User::query()
            ->whereNotIn('id', $relatedIds)
            ->latest()
            ->paginate(10, ['*'], 'page', (int) $request->get('page', 1));

        $html = view('friends._suggestions', ['users' => $users])->render();

        return response()->json([
            'html' => $html,
            'next_page' => $users->hasMorePages() ? $users->currentPage() + 1 : null,
            'has_more' => $users->hasMorePages(),
        ]);
    }

    public function requestsIncomingInfinite(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $requests = Friendship::query()
            ->with('user')
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->paginate(10, ['*'], 'page', (int) $request->get('page', 1));

        $html = view('friends._incoming', ['requests' => $requests])->render();

        return response()->json([
            'html' => $html,
            'next_page' => $requests->hasMorePages() ? $requests->currentPage() + 1 : null,
            'has_more' => $requests->hasMorePages(),
        ]);
    }

    public function requestsOutgoingInfinite(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $requests = Friendship::query()
            ->with('friend')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->latest()
            ->paginate(10, ['*'], 'page', (int) $request->get('page', 1));

        $html = view('friends._outgoing', ['requests' => $requests])->render();

        return response()->json([
            'html' => $html,
            'next_page' => $requests->hasMorePages() ? $requests->currentPage() + 1 : null,
            'has_more' => $requests->hasMorePages(),
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
