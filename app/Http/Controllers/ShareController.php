<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Notifications\ActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShareController extends Controller
{
    public function index(Post $post): View
    {
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

    public function store(Request $request, Post $post): JsonResponse|RedirectResponse
    {
        $this->authorize('view', $post);

        $rootOriginal = $post->rootOriginalPost();
        $user = $request->user();
        $isOwner = $rootOriginal->user_id === $user->id;

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

        if ($rootOriginal->user_id !== $user->id) {
            $rootOriginal->user->notify(new ActivityNotification('post_share', ['user_id' => $user->id, 'user_name' => $user->name, 'post_id' => $share->id]));
        }

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
}