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

    public function postsMade(): HasMany
    {
        return $this->posts();
    }
}
