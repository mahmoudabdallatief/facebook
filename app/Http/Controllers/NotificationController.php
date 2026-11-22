<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->take(50)->get();
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
            $postId = $notification->data['post_id'] ?? null;

            if ($postId) {
                $request->user()->notifications()
                    ->where('read_at', null)
                    ->whereJsonContains('data', ['post_id' => $postId])
                    ->update(['read_at' => now()]);
            } else {
                $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
            }
        }

        $url = $request->query('url');
        return $url ? redirect($url) : back();
    }
}
