<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageTyping;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;

use App\Models\{Conversation, Friendship, Message, User};
class MessengerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $blockedIds = $user->blockedRelationUserIds();

        // إنشاء محادثات تلقائية للأصدقاء الذين ليس لديهم محادثة بعد (باستثناء المحظورين)
        foreach ($user->friends() as $friend) {
            if (! in_array($friend->id, $blockedIds, true)) {
                Conversation::getOrCreate($user, $friend);
            }
        }

        $conversations = Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('user_one_id', $user->id)
                    ->orWhere('user_two_id', $user->id);
            })
            ->when(! empty($blockedIds), function ($q) use ($user, $blockedIds) {
                $q->whereNot(function ($q) use ($user, $blockedIds) {
                    $q->where(function ($q2) use ($user, $blockedIds) {
                        $q2->where('user_one_id', $user->id)
                            ->whereIn('user_two_id', $blockedIds);
                    })->orWhere(function ($q2) use ($user, $blockedIds) {
                        $q2->where('user_two_id', $user->id)
                            ->whereIn('user_one_id', $blockedIds);
                    });
                });
            })
            ->with([
                'userOne',
                'userTwo',
                'lastMessage' => function ($q) {
                    $q->with('sender', 'media');
                },
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('sender_id', '!=', $user->id)
                    ->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $conversations->getCollection()->transform(function ($conversation) use ($user) {
            $conversation->other_user = $conversation->otherUser($user);
            return $conversation;
        });

        return view('messenger.index', [
            'conversations' => $conversations,
        ]);
    }

    private function assertNotBlocked(User $user, User $other): void
    {
        if ($user->hasBlockRelationWith($other)) {
            abort(403);
        }
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        $this->assertNotBlocked($user, $conversation->otherUser($user));

        $otherUser = $conversation->otherUser($user);

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->get()
            ->each(function (Message $message) use ($user): void {
                $message->markAsRead();
                broadcast(new MessageRead($message, $user->id))->toOthers();
            });

        $messages = $conversation->messages()
            ->with(['sender', 'media'])
            ->latest()
            ->paginate(50, ['*'], 'page', 1)
            ->reverse();

        return view('messenger.conversation', [
            'conversation' => $conversation,
            'otherUser' => $otherUser,
            'messages' => $messages,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);
        $auth = $request->user();
        $user = User::findOrFail($validated['user_id']);

        if ($auth->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot start conversation with yourself'], 422);
        }

        if ($auth->hasBlockRelationWith($user)) {
            return response()->json(['success' => false, 'message' => 'Cannot start conversation with a blocked user'], 403);
        }

        $conversation = Conversation::getOrCreate($auth, $user);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'redirect' => route('messenger.show', $conversation),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $user = $request->user();

        if (mb_strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $relatedIds = \App\Models\Friendship::query()
            ->where('user_id', $user->id)
            ->orWhere('friend_id', $user->id)
            ->get()
            ->flatMap(fn ($f) => [$f->user_id, $f->friend_id])
            ->push($user->id)
            ->unique()
            ->all();

        $users = User::query()
            ->whereNotIn('id', $relatedIds)
            ->when(! empty($blockedIds = $user->blockedRelationUserIds()), fn ($q) => $q->whereNotIn('id', $blockedIds))
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'avatar']);

        return response()->json(['users' => $users]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $user->unreadMessagesCount();

        return response()->json(['count' => $count]);
    }

    public function conversationItem(Request $request, Conversation $conversation): \Illuminate\View\View
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        $this->assertNotBlocked($user, $conversation->otherUser($user));

        $conversation->load(['userOne', 'userTwo']);
        $conversation->other_user = $conversation->otherUser($user);
        $conversation->lastMessage = $conversation->lastMessage;
        $conversation->unread_count = $conversation->getUnreadCount($user);

        return view('messenger.components.conversation-item', [
            'conversation' => $conversation,
        ]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        $this->assertNotBlocked($user, $conversation->otherUser($user));

        $page = $request->get('page', 1);
        $perPage = 50;

        $messages = $conversation->messages()
            ->with(['sender', 'media'])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $messages->getCollection()->transform(function ($message) use ($user) {
            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'type' => $message->type,
                'read_at' => $message->read_at?->toISOString(),
                'created_at' => $message->created_at->toISOString(),
                'is_own' => $message->sender_id === $user->id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar' => $message->sender->avatarUrl(),
                ],
                'media' => $message->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'type' => $media->type,
                        'url' => $media->url,
                        'metadata' => $media->metadata,
                    ];
                })->values()->toArray(),
            ];
        });

        // Mark unread messages as read when the conversation is opened
        $unreadMessages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->get();

        foreach ($unreadMessages as $message) {
            $message->markAsRead();
            broadcast(new MessageRead($message, $user->id))->toOthers();
        }

        return response()->json([
            'success' => true,
            'messages' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        $this->assertNotBlocked($user, $conversation->otherUser($user));

        $validated = $request->validate([
            'typing' => ['required', 'boolean'],
        ]);

        broadcast(new MessageTyping($conversation->id, $user->id, $user->name, (bool) $validated['typing']))->toOthers();

        return response()->json(['success' => true]);
    }

    public function presence(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        $this->assertNotBlocked($user, $conversation->otherUser($user));

        $user->forceFill(['last_seen_at' => now()])->saveQuietly();
        $otherUser = $conversation->otherUser($user)->fresh();

        return response()->json([
            'success' => true,
            'online' => $otherUser->isOnline(),
            'last_seen_at' => $otherUser->last_seen_at?->toISOString(),
        ]);
    }

    public function leavePresence(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        $this->assertNotBlocked($user, $conversation->otherUser($user));

        $user->forceFill(['last_seen_at' => null])->saveQuietly();

        return response()->json(['success' => true]);
    }
}
