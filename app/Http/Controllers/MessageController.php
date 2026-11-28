<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    private const MAX_IMAGE_SIZE = 50 * 1024 * 1024;
    private const MAX_VIDEO_SIZE = 2 * 1024 * 1024 * 1024;
    private const CHUNK_SIZE = 5 * 1024 * 1024;

    private const ALLOWED_VIDEO_MIMES = [
        'video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
    ];

    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
    ];

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        if ($user->hasBlockRelationWith($conversation->otherUser($user))) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
            'media_ids' => ['sometimes', 'array', 'max:10'],
            'media_ids.*' => ['integer', 'exists:message_media,id'],
        ]);

        $mediaIds = array_values(array_unique($validated['media_ids'] ?? []));
        if (trim($validated['content'] ?? '') === '' && $mediaIds === []) {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty'], 422);
        }

        $type = 'text';
        if ($mediaIds !== []) {
            $firstMedia = MessageMedia::query()->whereIn('id', $mediaIds)
                ->where('user_id', $user->id)->whereNull('message_id')
                ->where('status', 'completed')->orderBy('id')->first();
            if (!$firstMedia || MessageMedia::query()->whereIn('id', $mediaIds)
                ->where('user_id', $user->id)->whereNull('message_id')->where('status', 'completed')->count() !== count($mediaIds)) {
                return response()->json(['success' => false, 'message' => 'One or more attachments are unavailable'], 422);
            }
            if ($firstMedia) {
                $type = $firstMedia->type;
            }
        }

        $message = DB::transaction(function () use ($conversation, $user, $validated, $type, $mediaIds) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => trim($validated['content'] ?? '') ?: null,
                'type' => $type,
            ]);

            foreach ($mediaIds as $index => $mediaId) {
                $updated = MessageMedia::query()->whereKey($mediaId)->where('user_id', $user->id)
                    ->whereNull('message_id')->where('status', 'completed')
                    ->update(['message_id' => $message->id, 'order' => $index]);
                if (!$updated) {
                    throw new \RuntimeException('Attachment is no longer available');
                }
            }

            return $message;
        });

        $message->load(['sender', 'media']);

        $conversation->update(['last_message_at' => now()]);

        $otherUser = $conversation->otherUser($user);

        broadcast(new MessageSent($message, $otherUser->id))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $this->formatMessage($message, $user->id),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'file' => ['required', 'file'],
            'type' => ['required', 'in:image,video'],
        ]);

        $file = $validated['file'];
        $type = $validated['type'];

        $maxSize = $type === 'video' ? self::MAX_VIDEO_SIZE : self::MAX_IMAGE_SIZE;

        if ($file->getSize() > $maxSize) {
            return response()->json([
                'success' => false,
                'message' => $type === 'video' ? 'Video max size is 2GB' : 'Image max size is 50MB',
            ], 422);
        }

        $allowedMimeTypes = $type === 'video'
            ? self::ALLOWED_VIDEO_MIMES
            : self::ALLOWED_IMAGE_MIMES;

        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file type',
            ], 422);
        }

        $chunkSize = self::CHUNK_SIZE;
        $totalChunks = (int) ceil($file->getSize() / $chunkSize);
        $uploadId = Str::uuid()->toString();
        $filename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $slug = Str::slug(pathinfo($filename, PATHINFO_FILENAME));

        $media = MessageMedia::create([
            'user_id' => $user->id,
            'type' => $type,
            'disk' => 'public',
            'path' => '',
            'url' => '',
            'order' => 0,
            'upload_id' => $uploadId,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'uploaded_chunks' => 0,
            'total_size' => $file->getSize(),
            'status' => 'uploading',
            'metadata' => [
                'original_filename' => $filename,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ]);

        $tempPath = "uploads/chunks/{$uploadId}";
        Storage::disk('public')->makeDirectory($tempPath);

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'media_id' => $media->id,
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
        ]);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        if ($user->hasBlockRelationWith($conversation->otherUser($user))) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->get();

        foreach ($messages as $message) {
            $message->markAsRead();
            broadcast(new MessageRead($message, $user->id))->toOthers();
        }

        return response()->json(['success' => true]);
    }

    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $conversation->user_one_id === $user->id || $conversation->user_two_id === $user->id,
            403
        );

        if ($user->hasBlockRelationWith($conversation->otherUser($user))) {
            abort(403);
        }

        $page = $request->get('page', 1);
        $perPage = 50;

        $messages = $conversation->messages()
            ->with(['sender', 'media'])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $messages->getCollection()->transform(function ($message) use ($user) {
            return $this->formatMessage($message, $user->id);
        });

        // Mark unread messages as read
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

    private function formatMessage(Message $message, int $currentUserId): array
    {
        $isOwn = $message->sender_id === $currentUserId;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'content' => $message->content,
            'type' => $message->type,
            'read_at' => $message->read_at?->toISOString(),
            'created_at' => $message->created_at->toISOString(),
            'is_own' => $isOwn,
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
    }
}
