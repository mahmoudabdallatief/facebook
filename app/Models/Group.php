<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Group extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'cover_image',
        'avatar',
        'privacy',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public const PRIVACY_TYPES = ['public', 'private'];

    public const ROLES = ['admin', 'moderator', 'member'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function admins(): HasMany
    {
        return $this->members()->where('role', 'admin');
    }

    public function moderators(): HasMany
    {
        return $this->members()->where('role', 'moderator');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'group_id')->latest();
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

    public function totalReactions(): int
    {
        return $this->reactions()->count();
    }

    public function userReaction(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $this->reactions()->where('user_id', $user->id)->value('reaction_type');
    }

    public function memberRole(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($this->user_id === $user->id) {
            return 'admin';
        }

        return $this->members()->where('user_id', $user->id)->value('role');
    }

    public function isMember(?User $user): bool
    {
        return $this->memberRole($user) !== null;
    }

    public function canManage(?User $user): bool
    {
        $role = $this->memberRole($user);

        return in_array($role, ['admin', 'moderator'], true);
    }

    public function isAdmin(?User $user): bool
    {
        return $this->memberRole($user) === 'admin';
    }

    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        if ($viewer === null) {
            return $query;
        }

        $viewerId = $viewer->getKey();

        return $query->where(function (Builder $q) use ($viewerId): void {
            $q->where('privacy', 'public')
                ->orWhere('user_id', $viewerId)
                ->orWhere('privacy', 'private')
                ->orWhereExists(function ($q2) use ($viewerId): void {
                    $q2->selectRaw('1')
                        ->from('group_members')
                        ->whereColumn('group_members.group_id', 'groups.id')
                        ->where('group_members.user_id', $viewerId);
                });
        });
    }

    public function canView(?User $viewer): bool
    {
        if ($viewer !== null && $this->isOwnedBy($viewer)) {
            return true;
        }

        if ($this->privacy === 'public') {
            return true;
        }

        // Private groups are discoverable; their posts remain member-only.
        return $viewer !== null || $this->privacy === 'private';
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        return asset($this->cover_image);
    }

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

    public function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin' => __('app.role_admin'),
            'moderator' => __('app.role_moderator'),
            'member' => __('app.role_member'),
            default => $role,
        };
    }

    public function getPrivacyLabel(string $privacy): string
    {
        return match ($privacy) {
            'public' => __('app.page_privacy_public'),
            'closed' => __('app.page_privacy_closed'),
            'private' => __('app.page_privacy_private'),
            default => $privacy,
        };
    }
}
