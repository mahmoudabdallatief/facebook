<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['blocks', 'blockedBy']);
        $blockedIds = $user->blockedRelationUserIds();

        $notifications = $user->notifications()->latest()->take(200)->get();

        if (! empty($blockedIds)) {
            $notifications = $notifications->reject(function ($notification) use ($blockedIds): bool {
                return in_array($notification->data['user_id'] ?? null, $blockedIds, true);
            });
        }

        $notifications = $notifications->take(50);

        $postIds = $notifications
            ->pluck('data.post_id')
            ->filter()
            ->unique()
            ->values();

        $commentIds = $notifications
            ->filter(fn ($n) => empty($n->data['post_id']) && ! empty($n->data['comment_id']))
            ->pluck('data.comment_id')
            ->filter()
            ->unique()
            ->values();

        $notificationPosts = $postIds->isNotEmpty()
            ? Post::with(['group', 'page'])->whereIn('id', $postIds)->get()->keyBy('id')
            : collect();

        $notificationComments = $commentIds->isNotEmpty()
            ? Comment::with(['post.group', 'post.page'])->whereIn('id', $commentIds)->get()->keyBy('id')
            : collect();

        return view('notifications.index', compact('notifications', 'notificationPosts', 'notificationComments'));
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return back();
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification) {
            // تحديد الإشعار الحالي كمقروء أولاً وقبل كل شيء
            $notification->update(['read_at' => now()]);

            $type = $notification->data['type'] ?? null;
            $userId = $notification->data['user_id'] ?? null;

            if (in_array($type, ['friend_request', 'friend_accepted']) && $userId) {
                $request->user()->notifications()
                    ->where('read_at', null)
                    ->whereIn('type', ['friend_request', 'friend_accepted'])
                    ->whereJsonContains('data', ['user_id' => $userId])
                    ->update(['read_at' => now()]);
            } else {
                $postId = $notification->data['post_id'] ?? null;

                if ($postId) {
                    $request->user()->notifications()
                        ->where('read_at', null)
                        ->whereJsonContains('data', ['post_id' => $postId])
                        ->update(['read_at' => now()]);
                }
            }
        }

        $url = $request->query('url');
        return $url ? redirect($url) : back();
    }
}
