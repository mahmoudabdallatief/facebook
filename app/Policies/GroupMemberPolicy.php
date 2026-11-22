<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;

class GroupMemberPolicy
{
    public function view(?User $user, Group $group): bool
    {
        return $group->canView($user);
    }

    public function join(User $user, Group $group): bool
    {
        return ! $group->isMember($user);
    }

    public function leave(User $user, Group $group): bool
    {
        return $group->isMember($user) && ! $group->isAdmin($user);
    }

    public function update(User $user, GroupMember $member): bool
    {
        return $member->group->isAdmin($user) && $member->role !== 'admin';
    }

    public function delete(User $user, GroupMember $member): bool
    {
        $group = $member->group;

        if ($user->id === $member->user_id) {
            return ! $group->isAdmin($user);
        }

        return $group->isAdmin($user);
    }
}
