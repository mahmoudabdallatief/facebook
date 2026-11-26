<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Notifications\ActivityNotification;
use App\Services\CommunityNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request, Post $post, CommunityNotificationService $notifications): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => ['required', 'string', 'in:' . implode(',', Reaction::REACTION_TYPES)],
        ]);

        $reactionType = $validated['reaction_type'];
        $user = $request->user();

        $this->authorize('view', $post);

        $existing = $post->reactions()->where('user_id', $user->id)->first();

        if ($existing) {
            if ($existing->reaction_type === $reactionType) {
                $existing->delete();
                $action = 'removed';
            } else {
                $existing->update(['reaction_type' => $reactionType]);
                $action = 'updated';
            }
        } else {
            $post->reactions()->create([
                'user_id' => $user->id,
                'reaction_type' => $reactionType,
            ]);
            $action = 'added';

            $notifications->notifyPostAudience($post, $user, 'post_reaction', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'post_id' => $post->id,
                'reaction_type' => $reactionType,
            ]);
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'reaction_type' => $action === 'removed' ? null : $reactionType,
            'counts' => $post->reactionCounts(),
            'total' => $post->totalReactions(),
            'user_reaction' => $action === 'removed' ? null : $reactionType,
        ]);
    }

    public function storeOnComment(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => ['required', 'string', 'in:' . implode(',', Reaction::REACTION_TYPES)],
        ]);

        $reactionType = $validated['reaction_type'];
        $user = $request->user();

        $this->authorize('view', $comment->post);

        if ($user->hasBlockRelationWith($comment->user)) {
            return response()->json(['success' => false, 'message' => __('app.comment_blocked_interaction')], 403);
        }

        $existing = $comment->reactions()->where('user_id', $user->id)->first();

        if ($existing) {
            if ($existing->reaction_type === $reactionType) {
                $existing->delete();
                $action = 'removed';
            } else {
                $existing->update(['reaction_type' => $reactionType]);
                $action = 'updated';
            }
        } else {
            $comment->reactions()->create([
                'user_id' => $user->id,
                'reaction_type' => $reactionType,
            ]);
            $action = 'added';

            if ($comment->user_id !== $user->id) {
                $comment->user->notify(new ActivityNotification('comment_reaction', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'comment_id' => $comment->id,
                    'reaction_type' => $reactionType,
                    'post_id' => $comment->post_id,
                ]));
            }
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'reaction_type' => $action === 'removed' ? null : $reactionType,
            'counts' => $comment->reactionCounts(),
            'total' => $comment->reactions()->count(),
            'user_reaction' => $action === 'removed' ? null : $reactionType,
        ]);
    }
}
