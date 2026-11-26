<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'page_id',
        'group_id',
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

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function isPagePost(): bool
    {
        return $this->page_id !== null;
    }

    public function isGroupPost(): bool
    {
        return $this->group_id !== null;
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

    public function canShareAgain(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return true; // Users can share multiple times
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        if ($viewer === null) {
            return $query->where(function ($q): void {
                $q->whereNull('page_id')
                    ->whereNull('group_id')
                    ->where('visibility', 'public')
                    ->orWhere(function ($q2): void {
                        $q2->whereNotNull('group_id')
                            ->whereExists(function ($q3): void {
                                $q3->selectRaw('1')
                                    ->from('groups')
                                    ->whereColumn('groups.id', 'posts.group_id')
                                    ->where('groups.privacy', 'public');
                            });
                    });
            });
        }

        $viewerId = $viewer->getKey();
        $blockedIds = $viewer->blockedRelationUserIds();

        return $query->where(function (Builder $q) use ($viewerId, $blockedIds): void {
                $q->where(function (Builder $ownPost) use ($viewerId): void {
                    $ownPost->where('user_id', $viewerId)
                        ->whereNull('page_id');
                })
                ->orWhere(function (Builder $q2): void {
                    $q2->whereNull('page_id')
                        ->whereNull('group_id')
                        ->where('visibility', 'public');
                })
                ->orWhere(function (Builder $q2) use ($viewerId): void {
                    $q2->whereNotNull('page_id')
                        ->whereExists(function ($q3) use ($viewerId): void {
                            $q3->selectRaw('1')
                                ->from('pages')
                                ->whereColumn('pages.id', 'posts.page_id')
                                ->where(function ($q4) use ($viewerId): void {
                                    $q4->where('user_id', $viewerId)
                                        ->orWhereExists(function ($q5) use ($viewerId): void {
                                            $q5->selectRaw('1')
                                                ->from('page_members')
                                                ->whereColumn('page_members.page_id', 'pages.id')
                                                ->where('page_members.user_id', $viewerId);
                                        });
                                });
                        });
                })
                ->orWhere(function (Builder $q2) use ($viewerId): void {
                    $q2->whereNotNull('group_id')
                        ->whereExists(function ($q3) use ($viewerId): void {
                            $q3->selectRaw('1')
                                ->from('groups')
                                ->whereColumn('groups.id', 'posts.group_id')
                                ->where(function ($q4) use ($viewerId): void {
                                    $q4->where('privacy', 'public')
                                        ->orWhere('user_id', $viewerId)
                                        ->orWhereExists(function ($q5) use ($viewerId): void {
                                            $q5->selectRaw('1')
                                                ->from('group_members')
                                                ->whereColumn('group_members.group_id', 'groups.id')
                                                ->where('group_members.user_id', $viewerId);
                                        });
                                });
                        });
                })
                ->orWhere(function (Builder $q2) use ($viewerId): void {
                    $q2->whereNull('page_id')
                        ->whereNull('group_id')
                        ->where('visibility', 'friends')
                        ->whereRaw('EXISTS (
                            SELECT 1 FROM friendships
                            WHERE status = "accepted"
                            AND (
                                (friendships.user_id = posts.user_id AND friendships.friend_id = ?)
                                OR (friendships.friend_id = posts.user_id AND friendships.user_id = ?)
                            )
                        )', [$viewerId, $viewerId]);
                });
        })
        ->when(! empty($blockedIds), function (Builder $q) use ($blockedIds): void {
            $q->whereNotIn('posts.user_id', $blockedIds)
                ->whereNotExists(function ($q2) use ($blockedIds): void {
                    $q2->selectRaw('1')
                        ->from('posts AS original')
                        ->whereColumn('original.id', 'posts.original_post_id')
                        ->whereIn('original.user_id', $blockedIds);
                });
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function canView(?User $viewer): bool
    {
        if ($viewer !== null && $viewer->hasBlockRelationWith($this->user)) {
            return false;
        }

        if ($viewer !== null && $this->isOwnedBy($viewer)) {
            return true;
        }

        if ($this->isGroupPost() && $this->group) {
            return $this->group->canView($viewer);
        }

        if ($this->isPagePost() && $this->page) {
            return $this->page->canView($viewer);
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

    public function commentCount(): int
    {
        return $this->allComments()->count();
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

    public function getShareUrl(): string
    {
        if ($this->isGroupPost() && $this->group) {
            return route('groups.posts.show', ['group' => $this->group, 'post' => $this]);
        }

        if ($this->isPagePost() && $this->page) {
            return route('pages.posts.show', ['page' => $this->page, 'post' => $this]);
        }

        return route('posts.show', $this);
    }

    public function getOriginalPostUrl(): ?string
    {
        if (! $this->isShare()) {
            return null;
        }

        $root = $this->rootOriginalPost();

        if ($root->isGroupPost() && $root->group) {
            return route('groups.posts.show', ['group' => $root->group, 'post' => $root]);
        }

        if ($root->isPagePost() && $root->page) {
            return route('pages.posts.show', ['page' => $root->page, 'post' => $root]);
        }

        return route('posts.show', $root);
    }
}
