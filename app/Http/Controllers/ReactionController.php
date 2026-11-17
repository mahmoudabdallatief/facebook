<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'reaction_type' => ['required', 'string', 'in:' . implode(',', Reaction::REACTION_TYPES)],
        ]);

        $reactionType = $validated['reaction_type'];
        $user = $request->user();

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
