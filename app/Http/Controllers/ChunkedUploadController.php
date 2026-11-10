<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideo;
use App\Models\PostMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    private const CHUNK_SIZE = 5 * 1024 * 1024;

    private const MAX_FILE_SIZE = 2 * 1024 * 1024 * 1024;

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'file_size' => ['required', 'integer', 'min:1', 'max:'.self::MAX_FILE_SIZE],
            'mime_type' => ['required', 'string', 'in:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska'],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'chunk_size' => ['sometimes', 'integer', 'min:1', 'max:'.self::CHUNK_SIZE],
        ]);

        $chunkSize = $validated['chunk_size'] ?? self::CHUNK_SIZE;
        if ((int) ceil($validated['file_size'] / $chunkSize) !== $validated['total_chunks']) {
            return response()->json(['success' => false, 'message' => 'Invalid chunk count'], 422);
        }

        $uploadId = Str::uuid()->toString();
        $type = 'video';

        $media = PostMedia::create([
            'post_id' => null,
            'user_id' => $request->user()->id,
            'type' => $type,
            'disk' => 'public',
            'path' => '',
            'url' => '',
            'order' => 0,
            'upload_id' => $uploadId,
            'chunk_size' => $chunkSize,
            'total_chunks' => $validated['total_chunks'],
            'uploaded_chunks' => 0,
            'total_size' => $validated['file_size'],
            'status' => 'uploading',
            'metadata' => [
                'original_filename' => $validated['filename'],
                'mime_type' => $validated['mime_type'],
            ],
        ]);

        $tempPath = "uploads/chunks/{$uploadId}";
        Storage::disk('public')->makeDirectory($tempPath);

        return response()->json([
            'success' => true,
            'upload_id' => $uploadId,
            'media_id' => $media->id,
            'chunk_size' => $chunkSize,
        ]);
    }

    public function uploadChunk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'uuid'],
            'chunk_index' => ['required', 'integer', 'min:0'],
            'chunk' => ['required', 'file', 'max:'.(self::CHUNK_SIZE / 1024)],
        ]);

        $media = PostMedia::where('upload_id', $validated['upload_id'])->firstOrFail();
        abort_unless($media->user_id === $request->user()->id, 403);

        if ($media->status !== 'uploading') {
            return response()->json([
                'success' => false,
                'message' => 'Upload is not in progress',
            ], 400);
        }

        if ($validated['chunk_index'] >= $media->total_chunks) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid chunk index',
            ], 400);
        }

        $chunk = $request->file('chunk');
        if ($chunk->getSize() < 1 || $chunk->getSize() > $media->chunk_size) {
            return response()->json(['success' => false, 'message' => 'Invalid chunk size'], 422);
        }
        $tempPath = "uploads/chunks/{$media->upload_id}/chunk_{$validated['chunk_index']}";
        if (! Storage::disk('public')->exists($tempPath)) {
            $chunk->storeAs(dirname($tempPath), basename($tempPath), 'public');
        }

        $uploadedChunks = count(Storage::disk('public')->files("uploads/chunks/{$media->upload_id}"));
        $media->update(['uploaded_chunks' => $uploadedChunks]);

        if ($uploadedChunks >= $media->total_chunks
            && PostMedia::whereKey($media->id)->where('status', 'uploading')->update(['status' => 'assembling'])) {
            $this->assembleChunks($media->fresh());
        }

        return response()->json([
            'success' => true,
            'progress' => $media->getProgress(),
            'uploaded_chunks' => $media->uploaded_chunks,
            'total_chunks' => $media->total_chunks,
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'uuid'],
            'post_id' => ['sometimes', 'integer', 'exists:posts,id'],
        ]);

        $media = PostMedia::where('upload_id', $validated['upload_id'])->firstOrFail();
        abort_unless($media->user_id === $request->user()->id, 403);

        if ($media->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Upload not completed yet',
            ], 400);
        }

        if ($validated['post_id'] ?? null) {
            $post = \App\Models\Post::findOrFail($validated['post_id']);
            abort_unless($post->user_id === $request->user()->id, 403);
            $media->update([
                'post_id' => $post->id,
                'order' => ($post->media()->max('order') ?? 0) + 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'media' => $media->load('post'),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'uuid'],
        ]);

        $media = PostMedia::where('upload_id', $validated['upload_id'])->firstOrFail();
        abort_unless($media->user_id === $request->user()->id, 403);

        return response()->json([
            'success' => true,
            'status' => $media->status,
            'progress' => $media->getProgress(),
            'uploaded_chunks' => $media->uploaded_chunks,
            'total_chunks' => $media->total_chunks,
            'media' => $media->status === 'completed' ? $media : null,
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'string', 'uuid'],
        ]);

        $media = PostMedia::where('upload_id', $validated['upload_id'])->firstOrFail();
        abort_unless($media->user_id === $request->user()->id, 403);

        $this->cleanupTempFiles($media);
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Upload cancelled',
        ]);
    }

    private function assembleChunks(PostMedia $media): void
    {
        $tempDir = "uploads/chunks/{$media->upload_id}";
        $filename = $media->metadata['original_filename'] ?? 'video.mp4';
        $extension = preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($filename, PATHINFO_EXTENSION)) ?: 'mp4';
        $finalPath = "posts/{$media->upload_id}/".Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'.'.$extension;

        $chunks = [];
        for ($i = 0; $i < $media->total_chunks; $i++) {
            $chunkPath = "{$tempDir}/chunk_{$i}";
            if (! Storage::disk('public')->exists($chunkPath)) {
                $media->update(['status' => 'failed', 'metadata' => array_merge($media->metadata ?? [], ['error' => "Missing chunk {$i}"])]);
                $this->cleanupTempFiles($media);
                return;
            }
            $chunks[] = $chunkPath;
        }

        Storage::disk('public')->makeDirectory(dirname($finalPath));
        $target = fopen(Storage::disk('public')->path($finalPath), 'wb');
        if ($target === false) {
            $media->update(['status' => 'failed']);
            return;
        }

        foreach ($chunks as $chunkPath) {
            $source = Storage::disk('public')->readStream($chunkPath);
            if ($source === false) {
                fclose($target);
                $media->update(['status' => 'failed']);
                return;
            }
            stream_copy_to_stream($source, $target);
            fclose($source);
        }
        fclose($target);

        $finalFile = Storage::disk('public')->path($finalPath);
        if (filesize($finalFile) !== $media->total_size) {
            Storage::disk('public')->delete($finalPath);
            $media->update(['status' => 'failed', 'metadata' => array_merge($media->metadata ?? [], ['error' => 'Assembled file size does not match the original upload'])]);
            $this->cleanupTempFiles($media);
            return;
        }

        $mimeType = mime_content_type($finalFile);
        if (! is_string($mimeType) || ! str_starts_with($mimeType, 'video/')) {
            Storage::disk('public')->delete($finalPath);
            $media->update(['status' => 'failed']);
            $this->cleanupTempFiles($media);
            return;
        }

        $this->cleanupTempFiles($media);
        $media->update([
            'path' => $finalPath,
            'url' => 'storage/'.$finalPath,
            'status' => 'completed',
        ]);
        ProcessVideo::dispatch($media);
    }

    private function cleanupTempFiles(PostMedia $media): void
    {
        $tempDir = "uploads/chunks/{$media->upload_id}";
        Storage::disk('public')->deleteDirectory($tempDir);
    }
}
