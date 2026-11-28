<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', File::image()->max(2048)],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $user->avatar));
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = 'storage/'.$path;
        }

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.profile_updated'),
                'user' => [
                    'name' => $user->name,
                    'avatar' => $user->avatarUrl(),
                    'bio' => $user->bio,
                ],
            ]);
        }

        return redirect()->route('dashboard')
            ->with('status', __('app.profile_updated'));
    }

    public function show(User $user): View
    {
        $viewer = auth()->user();
        $viewer?->loadMissing([
            'blocks',
            'blockedBy',
            'friendsOfMine',
            'friendOf',
            'sentFriendRequests',
            'receivedFriendRequests',
        ]);

        if ($viewer && $viewer->hasBlockRelationWith($user)) {
            abort(403);
        }

        if ($user->isProfileLocked() && (! $viewer || ! $viewer->is($user))) {
            return view('profile.locked', [
                'profileUser' => $user,
            ]);
        }

        $blockedIds = $viewer ? $viewer->blockedRelationUserIds() : [];

        $posts = $user->posts()
            ->whereNull('group_id')
            ->withFeedRelations($viewer)
            ->when(! empty($blockedIds), function ($q) use ($blockedIds): void {
                $q->whereNotIn('user_id', $blockedIds)
                    ->whereNotExists(function ($q2) use ($blockedIds): void {
                        $q2->selectRaw('1')
                            ->from('posts AS original')
                            ->whereColumn('original.id', 'posts.original_post_id')
                            ->whereIn('original.user_id', $blockedIds);
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('profile.show', [
            'profileUser' => $user,
            'posts' => $posts,
        ]);
    }

    public function infinite(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();
        $viewer?->loadMissing(['blocks', 'blockedBy']);
        $blockedIds = $viewer ? $viewer->blockedRelationUserIds() : [];

        $posts = $user->posts()
            ->whereNull('group_id')
            ->withFeedRelations($viewer)
            ->when(! empty($blockedIds), function ($q) use ($blockedIds): void {
                $q->whereNotIn('user_id', $blockedIds)
                    ->whereNotExists(function ($q2) use ($blockedIds): void {
                        $q2->selectRaw('1')
                            ->from('posts AS original')
                            ->whereColumn('original.id', 'posts.original_post_id')
                            ->whereIn('original.user_id', $blockedIds);
                    });
            })
            ->latest()
            ->paginate(10, ['*'], 'page', (int) $request->get('page', 1));

        $html = view('profile._posts', ['posts' => $posts])->render();

        return response()->json([
            'html' => $html,
            'next_page' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
            'has_more' => $posts->hasMorePages(),
        ]);
    }
}
