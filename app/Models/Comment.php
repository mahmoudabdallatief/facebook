<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Comment extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->latest();
    }

    public function allChildren(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function rootComment(): Comment
    {
        return $this->parent ?? $this;
    }

    public function depth(): int
    {
        $depth = 0;
        $comment = $this;

        while ($comment->parent) {
            $depth++;
            $comment = $comment->parent;
        }

        return $depth;
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function reactionCounts(): array
    {
        $counts = [];

        foreach (Reaction::REACTION_TYPES as $type) {
            $counts[$type] = $this->reactions()->where('reaction_type', $type)->count();
        }

        return $counts;
    }

    public function userReaction(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $this->reactions()->where('user_id', $user->id)->value('reaction_type');
    }

    public function totalReactions(): int
    {
        return $this->reactions()->count();
    }

    public function getReplyCount(): int
    {
        return $this->children()->count();
    }

    public function getAllDescendantsCount(): int
    {
        $count = 0;
        $queue = [$this];

        while (! empty($queue)) {
            $comment = array_shift($queue);
            $children = $comment->children;
            $count += $children->count();
            $queue = array_merge($queue, $children->toArray());
        }

        return $count;
    }

    public static function buildTree(Collection $comments): array
    {
        $byId = $comments->keyBy('id')->all();
        $roots = [];

        foreach ($comments as $comment) {
            $comment->setRelation('children', collect());
        }

        foreach ($comments as $comment) {
            if ($comment->parent_id === null) {
                $roots[] = $comment;
            } elseif (isset($byId[$comment->parent_id])) {
                $byId[$comment->parent_id]->children->push($comment);
            } else {
                $roots[] = $comment;
            }
        }

        usort($roots, fn ($a, $b) => $b->created_at->timestamp <=> $a->created_at->timestamp);

        foreach ($comments as $comment) {
            $comment->setRelation('children', $comment->children->sortByDesc('created_at')->values());
        }

        return $roots;
    }

    public function toTreeArray(?User $viewer = null): array
    {
        $me = $viewer ?? auth()->user();
        $blockedForViewer = $me && $me->hasBlockRelationWith($this->user);

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'body' => $this->body,
            'blocked_for_viewer' => (bool) $blockedForViewer,
            'user' => $this->user,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'depth' => $this->depth(),
            'is_reply' => $this->isReply(),
            'parent_id' => $this->parent_id,
            'reaction_counts' => $this->reactionCounts(),
            'total_reactions' => $this->totalReactions(),
            'user_reaction' => $this->userReaction($me),
            'can_edit' => $me && $me->id === $this->user_id,
            'can_delete' => $me && $me->id === $this->user_id,
            'children' => $this->children->map(fn ($child) => $child->toTreeArray($me))->toArray(),
        ];
    }
}
