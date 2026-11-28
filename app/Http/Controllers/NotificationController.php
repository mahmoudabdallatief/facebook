<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $this->notificationsPage($request, false);
    }

    public function infinite(Request $request): JsonResponse
    {
        return $this->notificationsPage($request, true);
    }

    private function notificationsPage(Request $request, bool $json)
    {
        $user = $request->user();
        $user->loadMissing(['blocks', 'blockedBy']);
        $blockedIds = $user->blockedRelationUserIds();

        $notifications = $user->notifications()->latest()->paginate(10, ['*'], 'page', (int) $request->get('page', 1));

        $filtered = ! empty($blockedIds)
            ? $notifications->getCollection()->reject(function ($notification) use ($blockedIds): bool {
                return in_array($notification->data['user_id'] ?? null, $blockedIds, true);
            })
            : $notifications->getCollection();

        $postIds = $filtered
            ->pluck('data.post_id')
            ->filter()
            ->unique()
            ->values();

        $commentIds = $filtered
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

        if ($json) {
            $html = view('notifications._items', [
                'notifications' => $filtered,
                'notificationPosts' => $notificationPosts,
                'notificationComments' => $notificationComments,
            ])->render();

            return response()->json([
                'html' => $html,
                'next_page' => $notifications->hasMorePages() ? $notifications->currentPage() + 1 : null,
                'has_more' => $notifications->hasMorePages(),
            ]);
        }

        return view('notifications.index', compact('filtered', 'notificationPosts', 'notificationComments', 'notifications'));
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
