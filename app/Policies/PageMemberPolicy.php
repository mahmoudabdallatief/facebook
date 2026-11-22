<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\PageMember;
use App\Models\User;

class PageMemberPolicy
{
    public function view(?User $user, Page $page): bool
    {
        return $page->canView($user);
    }

    public function join(User $user, Page $page): bool
    {
        return $page->isMember($user) === false;
    }

    public function leave(User $user, Page $page): bool
    {
        return $page->isMember($user) && ! $page->isAdmin($user);
    }

    public function update(User $user, PageMember $member): bool
    {
        return $member->page->isAdmin($user) && $member->role !== 'admin';
    }

    public function delete(User $user, PageMember $member): bool
    {
        $page = $member->page;

        if ($user->id === $member->user_id) {
            return ! $page->isAdmin($user);
        }

        return $page->isAdmin($user);
    }
}
