<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'bio',
        'theme',
        'profile_locked',
        'last_seen_at',
        'password',
    ];

    public function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return asset($this->avatar);
    }

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'profile_locked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    public function friendsOfMine(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withPivot('accepted_at')
            ->withTimestamps();
    }

    public function friendOf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 'accepted')
            ->withPivot('accepted_at')
            ->withTimestamps();
    }

    /** @return Collection<int, User> */
    public function friends()
    {
        return $this->friendsOfMine->merge($this->friendOf)->unique('id')->values();
    }

    public function friendshipWith(User $other): ?Friendship
    {
        return Friendship::query()
            ->where(function ($q) use ($other): void {
                $q->where('user_id', $this->id)->where('friend_id', $other->id);
            })
            ->orWhere(function ($q) use ($other): void {
                $q->where('user_id', $other->id)->where('friend_id', $this->id);
            })
            ->first();
    }

    public function isFriendWith(User $other): bool
    {
        $f = $this->friendshipWith($other);

        return $f !== null && $f->status === 'accepted';
    }

    public function hasPendingRequestTo(User $other): bool
    {
        return $this->sentFriendRequests()->where('friend_id', $other->id)->where('status', 'pending')->exists();
    }

    public function hasPendingRequestFrom(User $other): bool
    {
        return $this->receivedFriendRequests()->where('user_id', $other->id)->where('status', 'pending')->exists();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function pageMemberships(): HasMany
    {
        return $this->hasMany(PageMember::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function postsMade(): HasMany
    {
        return $this->posts();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'user_one_id')
            ->orWhere('user_two_id', $this->id);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->whereHas('conversation', function ($q) {
                $q->where('user_one_id', $this->id)
                    ->orWhere('user_two_id', $this->id);
            })
            ->where('sender_id', '!=', $this->id)
            ->whereNull('read_at')
            ->count();
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'user_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_user_id');
    }

    /**
     * IDs of users this user has blocked, users that blocked this user, and self.
     */
    public function blockedOrBlockingUserIds(): array
    {
        return $this->blocks()->pluck('blocked_user_id')
            ->merge($this->blockedBy()->pluck('user_id'))
            ->push($this->id)
            ->unique()
            ->all();
    }

    /**
     * IDs of users this user has blocked or that blocked this user (excludes self).
     */
    public function blockedRelationUserIds(): array
    {
        return $this->blocks()->pluck('blocked_user_id')
            ->merge($this->blockedBy()->pluck('user_id'))
            ->unique()
            ->all();
    }

    public function hasBlockRelationWith(User $other): bool
    {
        return $this->hasBlocked($other) || $this->isBlockedBy($other);
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_user_id')
            ->withTimestamps();
    }

    public function isBlockedBy(User $other): bool
    {
        return $this->blockedBy()->where('user_id', $other->id)->exists();
    }

    public function hasBlocked(User $other): bool
    {
        return $this->blocks()->where('blocked_user_id', $other->id)->exists();
    }

    public function isProfileLocked(): bool
    {
        return (bool) $this->profile_locked;
    }

    public function profileVisibleTo(User $viewer): bool
    {
        if ($this->is($viewer)) {
            return true;
        }

        if ($this->isBlockedBy($viewer) || $viewer->hasBlocked($this)) {
            return false;
        }

        if ($this->isProfileLocked() && ! $this->isFriendWith($viewer)) {
            return false;
        }

        return true;
    }
}
