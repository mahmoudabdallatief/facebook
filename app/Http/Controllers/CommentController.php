<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Notifications\ActivityNotification;
use App\Services\CommunityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CommentController extends Controller
{
    public function store(Request $request, CommunityNotificationService $notifications): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:comments,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $post = Post::findOrFail($validated['post_id']);

        $this->authorize('view', $post);

        if (isset($validated['parent_id'])) {
            $parent = Comment::where('id', $validated['parent_id'])
                ->where('post_id', $post->id)
                ->first();

            if (! $parent) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.comment_parent_invalid'),
                ], 422);
            }
        }

        $comment = $request->user()->comments()->create([
            'post_id' => $post->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        $comment->load('user');

        $auth = $request->user();
        if (! empty($validated['parent_id'])) {
            $parent = Comment::find($validated['parent_id']);
            if ($parent && $parent->user_id !== $auth->id) {
                $parent->user->notify(new ActivityNotification('comment_reply', [
                    'user_id' => $auth->id,
                    'user_name' => $auth->name,
                    'comment_id' => $parent->id,
                    'reply_id' => $comment->id,
                    'post_id' => $post->id,
                ]));
            }
        } else {
            $notifications->notifyPostAudience($post, $auth, 'post_comment', [
                'user_id' => $auth->id,
                'user_name' => $auth->name,
                'post_id' => $post->id,
                'comment_id' => $comment->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'comment' => $comment->toTreeArray($auth),
            'message' => __('app.comment_added'),
        ], 201);
    }

    public function index(Post $post): JsonResponse
    {
        $this->authorize('view', $post);

        // Load every level in one query, then assemble the tree in memory. Loading
        // only the root relationship silently dropped replies from the API result.
        $comments = $post->allComments()->with(['user', 'reactions'])->get();

        $tree = $this->buildTree($comments);

        return response()->json([
            'success' => true,
            'comments' => $tree,
        ]);
    }

    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update(['body' => $validated['body']]);

        return response()->json([
            'success' => true,
            'comment' => $comment->fresh()->toTreeArray(auth()->user()),
            'message' => __('app.comment_updated'),
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => __('app.comment_deleted'),
        ]);
    }

    private function buildTree(Collection $comments): array
    {
        $byId = $comments->keyBy('id')->all();
        $roots = [];

        foreach ($comments as $comment) {
            $comment->children_list = [];
        }

        foreach ($comments as $comment) {
            if ($comment->parent_id === null) {
                $roots[] = $comment;
            } elseif (isset($byId[$comment->parent_id])) {
                $byId[$comment->parent_id]->children_list[] = $comment;
            } else {
                $roots[] = $comment;
            }
        }

        return $this->collectionToArray($roots);
    }

    private function collectionToArray(array $comments): array
    {
        $result = [];

        foreach ($comments as $comment) {
            $item = [
                'id' => $comment->id,
                'body' => $comment->body,
                'user' => $comment->user,
                'created_at' => $comment->created_at,
                'reaction_counts' => $comment->reactionCounts(),
                'total_reactions' => $comment->reactions()->count(),
                'user_reaction' => $comment->userReaction(auth()->user()),
                'children' => $this->collectionToArray((array) $comment->children_list ?? []),
            ];
            unset($item['children_list']);
            $result[] = $item;
        }

        return $result;
    }

}
