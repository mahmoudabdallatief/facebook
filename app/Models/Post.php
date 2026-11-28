<?php

namespace App\Models;

use App\Models\Concerns\HasReactions;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use App\Models\PostMedia;
use App\Models\User;

class Post extends Model
{
    use HasReactions;
    protected $fillable = [
        'user_id',
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
        if (array_key_exists('shared_posts_count', $this->attributes)) {
            return (int) $this->attributes['shared_posts_count'];
        }

        if ($this->relationLoaded('sharedPosts')) {
            return $this->sharedPosts->count();
        }

        return $this->sharedPosts()->count();
    }

    public function hasSharedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (array_key_exists('shared_by_viewer_count', $this->attributes)) {
            return (int) $this->attributes['shared_by_viewer_count'] > 0;
        }

        if ($this->relationLoaded('sharedPosts')) {
            return $this->sharedPosts->contains('user_id', $user->id);
        }

        return $this->sharedPosts()->where('user_id', $user->id)->exists();
    }

    /**
     * Eager-loading relations map shared across feed, page, group, profile, and search queries.
     *
     * @return array<int|string, mixed>
     */
    public static function feedRelations(?User $viewer = null): array
    {
        return [
            'user',
            'media',
            'page.members:id,page_id,user_id,role',
            'group.members:id,group_id,user_id,role',
            'reactions.user',
            'allComments.user',
            'allComments.reactions.user',
            'originalPost' => function (BelongsTo $q) use ($viewer): void {
                $q->with([
                    'user',
                    'media',
                    'page.members:id,page_id,user_id,role',
                    'group.members:id,group_id,user_id,role',
                ])->withCount('sharedPosts');

                if ($viewer) {
                    $q->withCount([
                        'sharedPosts as shared_by_viewer_count' => fn ($sq) => $sq->where('user_id', $viewer->id),
                    ]);
                }
            },
        ];
    }

    public function scopeWithFeedRelations(Builder $query, ?User $viewer = null): Builder
    {
        $query->with(self::feedRelations($viewer))->withCount('sharedPosts');

        if ($viewer) {
            $query->withCount([
                'sharedPosts as shared_by_viewer_count' => fn (Builder $q) => $q->where('user_id', $viewer->id),
            ]);
        }

        return $query;
    }

    public function loadFeedRelations(?User $viewer = null): self
    {
        $this->loadMissing(self::feedRelations($viewer))->loadCount('sharedPosts');

        if ($viewer) {
            $this->loadCount([
                'sharedPosts as shared_by_viewer_count' => fn (Builder $q) => $q->where('user_id', $viewer->id),
            ]);
        }

        if ($this->original_post_id && $this->originalPost && ! array_key_exists('shared_posts_count', $this->originalPost->getAttributes())) {
            $this->originalPost->loadCount('sharedPosts');
            if ($viewer) {
                $this->originalPost->loadCount([
                    'sharedPosts as shared_by_viewer_count' => fn (Builder $q) => $q->where('user_id', $viewer->id),
                ]);
            }
        }

        return $this;
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

    public function commentCount(): int
    {
        if (array_key_exists('all_comments_count', $this->attributes)) {
            return (int) $this->attributes['all_comments_count'];
        }

        if ($this->relationLoaded('allComments')) {
            return $this->allComments->count();
        }

        return $this->allComments()->count();
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
