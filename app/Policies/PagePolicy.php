<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function view(?User $user, Page $page): bool
    {
        return $page->canView($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Page $page): bool
    {
        return $page->isAdmin($user);
    }

    public function delete(User $user, Page $page): bool
    {
        return $page->isAdmin($user);
    }

    public function manage(User $user, Page $page): bool
    {
        return $page->canManage($user);
    }

    public function join(User $user, Page $page): bool
    {
        return $page->type === 'page' && ! $page->isMember($user);
    }

    public function leave(User $user, Page $page): bool
    {
        return $page->type === 'page'
            && $page->user_id !== $user->id
            && $page->members()->where('user_id', $user->id)->exists();
    }
}
