<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function otherUser(User $user): ?User
    {
        return $this->user_one_id === $user->id ? $this->userTwo : $this->userOne;
    }

    public static function between(User $userOne, User $userTwo): ?self
    {
        $ids = [$userOne->id, $userTwo->id];
        sort($ids);

        return self::query()
            ->where('user_one_id', $ids[0])
            ->where('user_two_id', $ids[1])
            ->first();
    }

    public static function getOrCreate(User $userOne, User $userTwo): self
    {
        $existing = self::between($userOne, $userTwo);
        if ($existing) {
            return $existing;
        }

        $ids = [$userOne->id, $userTwo->id];
        sort($ids);

        return self::create([
            'user_one_id' => $ids[0],
            'user_two_id' => $ids[1],
        ]);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id);
        });
    }
}