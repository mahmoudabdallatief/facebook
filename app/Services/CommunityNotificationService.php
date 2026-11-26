<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Collection;

class CommunityNotificationService
{
    /** Notify the post author and the Page/Group managers, once per person. */
    public function notifyPostAudience(Post $post, User $actor, string $type, array $data): void
    {
        $recipients = new Collection;

        if ($post->user_id !== $actor->id) {
            $recipients->push($post->user);
        }

        if ($post->isPagePost() && $post->page) {
            $recipients = $recipients->merge(
                $post->page->admins()->with('user')->get()->pluck('user')
                    ->merge($post->page->moderators()->with('user')->get()->pluck('user'))
            );
        } elseif ($post->isGroupPost() && $post->group) {
            $recipients = $recipients->merge(
                $post->group->admins()->with('user')->get()->pluck('user')
                    ->merge($post->group->moderators()->with('user')->get()->pluck('user'))
            );
        }

        $recipients
            ->filter(fn ($recipient) => $recipient instanceof User && $recipient->id !== $actor->id && ! $actor->hasBlockRelationWith($recipient))
            ->unique('id')
            ->each(fn (User $recipient) => $recipient->notify(new ActivityNotification($type, $data)));
    }
}
