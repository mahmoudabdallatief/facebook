<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Group;
use App\Services\CommunityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShareController extends Controller
{
    public function index(Post $post): View
    {
        $post->loadMissing(['user', 'media', 'page.members:id,page_id,user_id,role', 'group.members:id,group_id,user_id,role']);
        $this->authorize('view', $post);

        $shares = $post->sharedPosts()
            ->with('user')
            ->paginate(20)
            ->withQueryString();

        return view('posts.shares', [
            'post' => $post,
            'shares' => $shares,
        ]);
    }

    public function store(Request $request, Post $post, CommunityNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $post);

        $rootOriginal = $post->rootOriginalPost();
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'in:public,friends'],
        ]);

        $share = $request->user()->posts()->create([
            'original_post_id' => $rootOriginal->id,
            'body' => $validated['body'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
        ]);

        $share->load(['user', 'originalPost.user', 'originalPost.media']);

        $notifications->notifyPostAudience($rootOriginal, $user, 'post_share', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'post_id' => $share->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.share_success'),
                'share' => $share,
            ], 201);
        }

        return redirect()->route('posts.index')
            ->with('status', __('app.share_success'));
    }

    public function storeInGroup(Request $request, Group $group, Post $post, CommunityNotificationService $notifications): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $post);
        $this->authorize('view', $group);

        if (! $group->isMember($request->user())) {
            abort(403, __('app.group_members_only'));
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['sometimes', 'in:public,friends'],
        ]);

        $rootOriginal = $post->rootOriginalPost();
        $user = $request->user();

        $share = $group->posts()->create([
            'user_id' => $user->id,
            'original_post_id' => $rootOriginal->id,
            'body' => $validated['body'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
        ]);

        $share->load(['user', 'group', 'originalPost.user', 'originalPost.media']);

        $notifications->notifyPostAudience($rootOriginal, $user, 'post_share', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'post_id' => $share->id,
            'group_id' => $group->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.share_success'),
                'share' => $share,
            ], 201);
        }

        return redirect()->route('groups.show', $group)
            ->with('status', __('app.share_success'));
    }

}
