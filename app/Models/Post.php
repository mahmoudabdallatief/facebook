<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
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
}
