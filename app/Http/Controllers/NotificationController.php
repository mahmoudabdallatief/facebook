<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $blockedIds = $user->blockedRelationUserIds();

        $notifications = $user->notifications()->latest()->take(200)->get();

        if (! empty($blockedIds)) {
            $notifications = $notifications->reject(function ($notification) use ($blockedIds): bool {
                return in_array($notification->data['user_id'] ?? null, $blockedIds, true);
            });
        }

        $notifications = $notifications->take(50);

        return view('notifications.index', compact('notifications'));
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
