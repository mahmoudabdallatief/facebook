<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'original_post_id',
        'body',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'original_post_id');
    }

    public function sharedPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'original_post_id')->latest();
    }

    public function isShare(): bool
    {
        return $this->original_post_id !== null;
    }

    public function rootOriginalPost(): Post
    {
        return $this->originalPost ?? $this;
    }

    public function shareCount(): int
    {
        return $this->sharedPosts()->count();
    }

    public function hasSharedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->sharedPosts()->where('user_id', $user->id)->exists();
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        if ($viewer === null) {
            return $query->where('visibility', 'public');
        }

        $viewerId = $viewer->getKey();

        return $query->where(function (Builder $q) use ($viewerId): void {
            $q->where('user_id', $viewerId)
                ->orWhere('visibility', 'public')
                ->orWhere(function (Builder $q2) use ($viewerId): void {
                    $q2->where('visibility', 'friends')
                        ->whereRaw('EXISTS (
                            SELECT 1 FROM friendships
                            WHERE status = "accepted"
                            AND (
                                (friendships.user_id = posts.user_id AND friendships.friend_id = ?)
                                OR (friendships.friend_id = posts.user_id AND friendships.user_id = ?)
                            )
                        )', [$viewerId, $viewerId]);
                });
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function canView(?User $viewer): bool
    {
        if ($viewer !== null && $this->isOwnedBy($viewer)) {
            return true;
        }

        if ($this->visibility === 'public') {
            return true;
        }

        if ($viewer === null || $this->visibility !== 'friends') {
            return false;
        }

        return $viewer->isFriendWith($this->user);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id')->whereNull('parent_id')->latest();
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id')->latest();
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
}
