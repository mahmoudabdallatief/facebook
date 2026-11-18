<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes();

Broadcast::channel('user.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId;
});

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId): bool {
    return \App\Models\Conversation::query()
        ->where('id', $conversationId)
        ->where(function ($q) use ($user) {
            $q->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id);
        })
        ->exists();
});