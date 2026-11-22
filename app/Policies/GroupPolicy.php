<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function view(?User $user, Group $group): bool
    {
        return $group->canView($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Group $group): bool
    {
        return $group->isAdmin($user);
    }

    public function delete(User $user, Group $group): bool
    {
        return $group->isAdmin($user);
    }

    public function manage(User $user, Group $group): bool
    {
        return $group->canManage($user);
    }

    public function join(User $user, Group $group): bool
    {
        return ! $group->isMember($user);
    }

    public function leave(User $user, Group $group): bool
    {
        return $group->isMember($user) && ! $group->isOwnedBy($user);
    }
}
